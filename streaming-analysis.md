# WordPress Core HTTP Streaming Analysis

## Full Call Chain

```
wp_remote_get($url, $args)                          # wp-includes/http.php
  └─► _wp_http_get_object()->get($url, $args)       # WP_Http singleton
        └─► WP_Http::request($url, $args)            # wp-includes/class-wp-http.php
              │
              ├─ apply_filters('pre_http_request')   # Short-circuit opportunity
              ├─ Build $options array with hooks, cookies, SSL, proxy
              │
              └─► Requests::request($url, $headers, $data, $type, $options)
                    │                                  # wp-includes/Requests/src/Requests.php
                    ├─ dispatch('requests.before_request')
                    ├─ get_transport() → Curl or Fsockopen
                    │
                    ├─► Transport::request($url, $headers, $data, $options)
                    │     │
                    │     │  [CURL TRANSPORT]           # Requests/src/Transport/Curl.php
                    │     ├─ setup_handle() → sets CURLOPT_WRITEFUNCTION = stream_body()
                    │     ├─ curl_exec()
                    │     │    └─ stream_body() called per chunk:
                    │     │         ├─ dispatch('request.progress', [$data, $bytes, $limit])
                    │     │         ├─ fwrite() to file  OR  $this->response_data .= $data
                    │     │         └─ return strlen($data)
                    │     ├─ process_response() → returns $this->headers (headers + body concatenated)
                    │     │
                    │     │  [FSOCKOPEN TRANSPORT]      # Requests/src/Transport/Fsockopen.php
                    │     ├─ fwrite($socket, $request)
                    │     ├─ while (!feof($socket)):
                    │     │    ├─ fread($socket, 1160)
                    │     │    ├─ dispatch('request.progress', [$block, $size, $max_bytes])
                    │     │    └─ $body .= $block  OR  fwrite($download, $block)
                    │     └─ return $headers . "\r\n\r\n" . $body
                    │
                    ├─ dispatch('requests.before_parse', [&$response, ...])
                    │
                    └─► Requests::parse_response($headers_string, ...)
                          │                            # Requests/src/Requests.php ~L390
                          ├─ new Response()
                          ├─ Split raw string at \r\n\r\n → headers vs body
                          ├─ decode_chunked($body)     # if transfer-encoding: chunked
                          ├─ decompress($body)         # if content-encoding: gzip/deflate
                          ├─ Handle redirects (recursive self::request())
                          ├─ dispatch('requests.after_request', [&$return, ...])
                          └─ return Response object
              │
              ├─► new WP_HTTP_Requests_Response($requests_response, $filename)
              │     └─ to_array() → ['headers'=>..., 'body'=>..., 'response'=>..., 'cookies'=>..., 'filename'=>...]
              │
              ├─ apply_filters('http_response', $response, $parsed_args, $url)
              └─ return $response array
```

## Layer-by-Layer Analysis

### Layer 1: `wp-includes/http.php` — Public API Functions

**What flows in/out:** URL + args array → response array with `headers`, `body`, `response`, `cookies`, `filename`.

**Buffering:** None at this layer — pure delegation.

**Streaming hooks:** None. These are thin wrappers around `WP_Http::request()`.

**No changes needed.** The new `'on_data'` arg passes through these functions unchanged, just like every other arg in `$args`.

---

### Layer 2: `wp-includes/class-wp-http.php` — `WP_Http::request()` (Lines 170–460)

**What flows in:** URL, args array with defaults (method, timeout, stream, filename, etc.)

**What flows out:** Response array: `['headers' => ..., 'body' => string, 'response' => [...], 'cookies' => [...]]`

**Buffering bottlenecks:**

1. **Line 415:** `$requests_response = WpOrg\Requests\Requests::request(...)` — Synchronous and blocking. The entire response must complete before control returns.

2. **Lines 418–419:** `$http_response = new WP_HTTP_Requests_Response($requests_response, ...)` then `$response = $http_response->to_array()` — The `to_array()` call reads the complete `$response->body` into the array's `'body'` key.

3. **Return value is an array with `'body' => string`** — Fundamentally incompatible with streaming. The body is a completed string.

**Existing hooks:**

- `'pre_http_request'` filter (line 277): Short-circuits the entire request. Not useful for streaming.
- `'http_request_args'` filter (line 252): Modifies args before the request. Could inject a callback.
- `'http_response'` filter (line 456): Fires after the complete response. Too late for streaming.
- `'http_api_debug'` action (line 440): Fires with complete response. Too late.

**Key observation:** `WP_Http::request()` already supports `'stream' => true` with `'filename'` (lines 310–323, passed at line 356), which sets `$options['filename']` passed down to Requests. This streams to a **file** but provides no mechanism for processing chunks in memory.

**Important detail:** When `'stream' => true` is set without a filename, line 311–312 auto-generates one: `$parsed_args['filename'] = get_temp_dir() . basename( $url )`. Lines 318–322 then check directory writability. This auto-filename behavior needs to be skipped when `on_data` is set (callback streaming doesn't need a file).

---

### Layer 3: `Requests/src/Requests.php` — `Requests::request()` and `parse_response()`

**What flows in:** URL, headers, data, type, options array.

**What flows out:** `Response` object (body as string property).

**`request()` method (lines 434–474):** Sets up options, calls transport, then `parse_response()` at line 473.

**`parse_response()` method (line 723–820):**

**Buffering bottlenecks:**

1. **Line 473:** `$response = $transport->request(...)` — Returns raw HTTP string (headers + body concatenated).

2. **Lines 733–745:** Body extraction — splits raw string at `\r\n\r\n`, assigns body to `$return->body`. The entire body is stored as a string on the Response object.

3. **Line 777:** `$return->body = self::decompress($return->body)` — operates on the complete body string. Hard blocker for streaming compressed responses.

4. **Lines 771–773:** `$return->body = self::decode_chunked($return->body)` — also operates on complete body.

**Hooks:**

- `'requests.before_request'` — fires before transport, args passed by reference.
- `'requests.before_parse'` — fires with the raw response string before parsing. Passed by reference.
- `'requests.after_request'` — fires after full parse. Too late.
- `'requests.before_redirect_check'` — after parse, before redirect logic.

---

### Layer 4: `Requests/src/Transport/Curl.php` — The Most Important Layer

**This is where streaming already partially exists.**

#### `stream_body()` method (Lines 533–559)

```php
public function stream_body($handle, $data) {
    $this->hooks->dispatch('request.progress', [$data, $this->response_bytes, $this->response_byte_limit]);
    $data_length = strlen($data);
    // ... byte limit checks ...
    if ($this->stream_handle) {
        fwrite($this->stream_handle, $data);
    } else {
        $this->response_data .= $data;     // ← BUFFERING BOTTLENECK (line 554)
    }
    $this->response_bytes += strlen($data);
    return $data_length;
}
```

**This is the key insight:** cURL already calls `stream_body()` chunk-by-chunk via `CURLOPT_WRITEFUNCTION` (set at line 457). Each chunk fires `'request.progress'` with the raw chunk data.

**The `request.progress` hook** receives:
1. `$data` — the raw chunk bytes
2. `$this->response_bytes` — bytes received so far
3. `$this->response_byte_limit` — max bytes limit

**This hook already receives chunk data during transfer.** However, it is insufficient for streaming because:
1. The data is still accumulated in `$this->response_data .= $data` regardless of the hook
2. After `curl_exec()` completes, `process_response()` (line 470) concatenates headers + body at line 481: `$this->headers .= $response`
3. The concatenated string is returned up the chain where it's re-parsed

**File streaming path (`$options['filename']`):** When a filename is set (line 177), `stream_handle` is opened and chunks are written to the file instead of accumulated in memory. This proves the architecture CAN support per-chunk processing — it just only supports writing to files.

#### `setup_handle()` method (Lines 362–459)

Sets `CURLOPT_WRITEFUNCTION` to `[$this, 'stream_body']` at line 457, only when `$options['blocking'] === true` (line 455). Also sets `CURLOPT_BUFFERSIZE` to `Requests::BUFFER_SIZE` (1160 bytes) at line 458.

**Note:** `CURLOPT_ENCODING` is set to `''` in the constructor (line 110), which tells cURL to handle decompression automatically. This means `stream_body()` receives **decompressed** data. This is beneficial for streaming — no post-processing needed.

---

### Layer 5: `Requests/src/Transport/Fsockopen.php`

**The read loop (lines 299–345):**

```php
while (!feof($socket)) {
    $this->info = stream_get_meta_data($socket);
    if ($this->info['timed_out']) {
        throw new Exception('fsocket timed out', 'timeout');
    }

    $block = fread($socket, Requests::BUFFER_SIZE);
    if (!$doingbody) {
        $response .= $block;
        if (strpos($response, "\r\n\r\n")) {
            list($headers, $block) = explode("\r\n\r\n", $response, 2);
            $doingbody             = true;
        }
    }

    if ($doingbody) {
        $options['hooks']->dispatch('request.progress', [$block, $size, $this->max_bytes]);
        // ... byte limit checks ...
        $size += strlen($block);
        if ($download) {
            fwrite($download, $block);
        } else {
            $body .= $block;    // ← BUFFERING BOTTLENECK (line 338)
        }
    }
}
```

**Same pattern as cURL:** `request.progress` hook fires per chunk, but data is still accumulated into `$body` string. Same file-streaming path exists via `$options['filename']` (line 292).

**Critical difference from cURL:** fsockopen does NOT handle decompression. Raw compressed data arrives in chunks. The decompression happens later in `Requests::parse_response()` on the complete body at line 777. For SSE/LLM APIs this is not a problem — SSE is text-based and APIs do not compress long-lived event streams.

---

### Layer 6: `Requests/src/Response.php`

Simple data container with `public $body = ''` (string). No streaming awareness. The `decode_body()` method (JSON parsing) also expects a complete string.

---

### Layer 7: `Requests/src/Hooks.php`

Standard event dispatcher. `dispatch()` calls registered callbacks synchronously with `$callback(...$parameters)`. Parameters can be passed by reference (the caller uses `[&$var]` syntax).

**Important:** Hooks are dispatched synchronously, so a streaming callback registered on `request.progress` would execute during the transport's data reception, which is exactly what we want.

---

### Layer 8: `wp-includes/class-wp-http-requests-response.php`

`WP_HTTP_Requests_Response::to_array()` (lines 186–196):

```php
return array(
    'headers'  => $this->get_headers(),
    'body'     => $this->get_data(),    // ← $this->response->body (complete string)
    'response' => array(
        'code'    => $this->get_status(),
        'message' => get_status_header_desc($this->get_status()),
    ),
    'cookies'  => $this->get_cookies(),
    'filename' => $this->filename,
);
```

**Final buffering point:** Even if streaming worked below, this method returns the body as a string.

---

## Identified Bottlenecks (Where Streaming Breaks)

| # | Location | Issue | Severity |
|---|----------|-------|----------|
| 1 | `Curl::stream_body()` line 554 | `$this->response_data .= $data` accumulates all chunks | **Low** — add `elseif` branch |
| 2 | `Fsockopen::request()` line 338 | `$body .= $block` accumulates all chunks | **Low** — add `elseif` branch |
| 3 | `Curl::process_response()` line 481 | `$this->headers .= $response` concatenates headers+body | **Medium** — skip when streaming |
| 4 | `Requests::parse_response()` line 733 | Body extraction expects headers+body in raw string | **High** — skip when streaming |
| 5 | `Requests::parse_response()` line 771 | `decode_chunked()` on complete body | **Medium** — skip when streaming |
| 6 | `Requests::parse_response()` line 777 | `decompress()` on complete body | **Low** for cURL (decompresses in-flight via `CURLOPT_ENCODING`), **N/A** for SSE |
| 7 | `WP_HTTP_Requests_Response::to_array()` line 190 | `'body' => string` in return array | **None** — body is empty string (same as file streaming) |
| 8 | `WP_Http::request()` line 419 | Returns flat array with string body | **None** — body is empty string (data delivered via callback) |

---

## Native Streaming API

WordPress's HTTP API was designed around a request-response model where the response is a completed string. Every layer — from the transport return value (concatenated string) through `Requests::parse_response()` (splits string) to `WP_HTTP_Requests_Response::to_array()` (body as string) — assumes the body is available in its entirety.

The solution is native streaming support — extending the existing `'stream' => true` pattern that already works for file downloads, so it also supports callback-based chunk processing. This is not a new subsystem; it completes one that was half-built.

### Requirements

1. **Long timeouts** — SSE streams can last minutes. Default 5s timeout is a non-starter.
2. **Chunked processing** — Each `data: {...}\n\n` event must be processable independently.
3. **No body accumulation** — LLM responses can be large; accumulating defeats the purpose.
4. **Decompression in-flight** — cURL already does this with `CURLOPT_ENCODING`. fsockopen would need `inflate_init()`/`inflate_add()` (PHP 7.0+).
5. **SSE protocol parsing** — Split on `\n\n`, parse `event:`, `data:`, `id:`, `retry:` fields.
6. **Early termination** — Ability to abort the stream (return -1 from cURL write callback, or close socket).
7. **Full WordPress integration** — proxy, SSL certificates, cookies, and hooks must all work.

---

## Implementation Spec

### 1. Design Principle

**Extend, don't invent.** WordPress already has `'stream' => true` with `'filename'` for streaming to files. This spec adds `'on_data'` as a peer to `'filename'` — same `'stream' => true` flag, new capability. When `'on_data'` is set, chunks go to the callback instead of (or in addition to) a file. When neither `'on_data'` nor `'filename'` is set, behavior is unchanged.

The pattern mirrors the existing file-streaming path exactly:
- **File streaming:** `'stream' => true, 'filename' => '/tmp/download.zip'` → chunks written to file
- **Callback streaming:** `'stream' => true, 'on_data' => callable` → chunks passed to callback
- **No streaming (default):** body accumulated in memory as today

This feels native because it uses the same flag and the same transport code paths. No new public functions required — `wp_remote_post()` gains streaming powers through its existing `$args` array.

### 2. Files to Modify (Call Chain Order)

#### 2.1 `wp-includes/http.php` — Document `on_data` in Public API

**No code changes needed.** The `wp_remote_*` functions are thin wrappers that pass `$args` through to `WP_Http::request()`. The new `'on_data'` arg is documented at the `WP_Http::request()` level.

The existing functions (`wp_remote_get()`, `wp_remote_post()`, etc.) all delegate to `$http->request()` or `$http->get()`/`$http->post()` without filtering args — they pass through cleanly.

#### 2.2 `wp-includes/class-wp-http.php` — `WP_Http::request()`

**Current code** — defaults array (lines 189–232):

```php
$defaults = array(
    'method'              => 'GET',
    'timeout'             => apply_filters( 'http_request_timeout', 5, $url ),
    // ... (with filter docblocks for each)
    'stream'              => false,     // line 230
    'filename'            => null,      // line 231
    'limit_response_size' => null,      // line 232
);
```

**Change 1 — Add `on_data` default:**

```diff
  'stream'              => false,
  'filename'            => null,
+ 'on_data'             => null,
  'limit_response_size' => null,
```

**Current code** — options passed to Requests (lines 347–366):

```php
$options = array(
    'timeout'   => $parsed_args['timeout'],
    'useragent' => $parsed_args['user-agent'],
    'blocking'  => $parsed_args['blocking'],
    'hooks'     => new WP_HTTP_Requests_Hooks( $url, $parsed_args ),
);
// ...
if ( $parsed_args['stream'] ) {
    $options['filename'] = $parsed_args['filename'];   // line 356
}
```

**Change 2 — Modify the `'stream' => true` block (lines 310–323) to skip auto-filename when `on_data` is set:**

The current code at line 310–323 auto-generates a temp filename and checks directory writability whenever `'stream' => true`. With `on_data`, no file is needed:

```diff
  if ( $parsed_args['stream'] ) {
-     if ( empty( $parsed_args['filename'] ) ) {
+     if ( empty( $parsed_args['filename'] ) && ! is_callable( $parsed_args['on_data'] ) ) {
          $parsed_args['filename'] = get_temp_dir() . basename( $url );
      }

      $parsed_args['blocking'] = true;
-     if ( ! wp_is_writable( dirname( $parsed_args['filename'] ) ) ) {
+     if ( ! empty( $parsed_args['filename'] ) && ! wp_is_writable( dirname( $parsed_args['filename'] ) ) ) {
          $response = new WP_Error( ... );
```

**Change 3 — Pass `on_data` through as `stream_callback` (after line 356):**

```diff
  if ( $parsed_args['stream'] ) {
      $options['filename'] = $parsed_args['filename'];
+     if ( is_callable( $parsed_args['on_data'] ) ) {
+         $options['stream_callback'] = $parsed_args['on_data'];
+     }
  }
```

**Current code** — response handling (lines 415–422):

```php
$requests_response = WpOrg\Requests\Requests::request( $url, $headers, $data, $type, $options );
$http_response = new WP_HTTP_Requests_Response( $requests_response, $parsed_args['filename'] );
$response      = $http_response->to_array();
$response['http_response'] = $http_response;
```

No change needed here. The body in the Response object is empty because the transport skipped accumulation. `to_array()` returns `'body' => ''` — the data was already delivered via callback. This matches the existing file-streaming behavior where `'body'` is also empty when `'filename'` is set.

#### 2.3 `wp-includes/Requests/src/Requests.php` — Skip Body Accumulation

**Current code** — `OPTION_DEFAULTS` (lines 112–131):

```php
const OPTION_DEFAULTS = [
    'timeout'          => 10,
    // ...
    'filename'         => false,
    // ...
    'verifyname'       => true,
];
```

**Change 1 — Add `stream_callback` default:**

```diff
  const OPTION_DEFAULTS = [
      'timeout'          => 10,
      // ...
      'filename'         => false,
+     'stream_callback'  => false,
      // ...
      'verifyname'       => true,
  ];
```

**Current code** — `parse_response()` (lines 723–820), body handling:

```php
// line 731
$return->body = '';

// lines 733–745
if (!$options['filename']) {
    $pos = strpos($headers, "\r\n\r\n");
    if ($pos === false) {
        throw new Exception('Missing header/body separator', 'requests.no_crlf_separator');
    }
    $headers = substr($return->raw, 0, $pos);
    $body = substr($return->raw, $pos + 4);
    if (!empty($body)) {
        $return->body = $body;
    }
}
```

**Change 2 — When `stream_callback` was used, skip body parsing and decompression:**

```diff
  $return->body = '';

- if (!$options['filename']) {
+ if (!$options['filename'] && !$options['stream_callback']) {
      $pos = strpos($headers, "\r\n\r\n");
      // ... existing body parsing ...
  }
```

And later, for chunked decoding and decompression (lines 771–777):

```php
if (isset($return->headers['transfer-encoding'])) {       // line 771
    $return->body = self::decode_chunked($return->body);   // line 772
    unset($return->headers['transfer-encoding']);           // line 773
}

if (isset($return->headers['content-encoding'])) {        // line 776
    $return->body = self::decompress($return->body);       // line 777
}
```

**Change 3 — Skip post-processing when body was streamed:**

```diff
- if (isset($return->headers['transfer-encoding'])) {
+ if (isset($return->headers['transfer-encoding']) && !$options['stream_callback']) {
      $return->body = self::decode_chunked($return->body);
      unset($return->headers['transfer-encoding']);
  }

- if (isset($return->headers['content-encoding'])) {
+ if (isset($return->headers['content-encoding']) && !$options['stream_callback']) {
      $return->body = self::decompress($return->body);
  }
```

**Why:** When `stream_callback` is active, the body was delivered chunk-by-chunk during transport. The `$headers` string from the transport contains only headers (no body appended), so splitting on `\r\n\r\n` for body extraction would throw `requests.no_crlf_separator`. Decompression is unnecessary because cURL decompresses via `CURLOPT_ENCODING` (set at Curl.php line 110), and SSE APIs do not use content-encoding on long-lived streams.

**Note on `$return->raw`:** When streaming, `$return->raw` contains only headers. This is a minor semantic change but acceptable — the raw body was already consumed.

#### 2.4 `wp-includes/Requests/src/Transport/Curl.php` — Core Streaming Change

**Current code** — `stream_body()` (lines 533–558):

```php
public function stream_body($handle, $data) {
    $this->hooks->dispatch('request.progress', [$data, $this->response_bytes, $this->response_byte_limit]);
    $data_length = strlen($data);

    // Are we limiting the response size?
    if ($this->response_byte_limit) {
        if ($this->response_bytes === $this->response_byte_limit) {
            return $data_length;
        }
        if (($this->response_bytes + $data_length) > $this->response_byte_limit) {
            $limited_length = ($this->response_byte_limit - $this->response_bytes);
            $data           = substr($data, 0, $limited_length);
        }
    }

    if ($this->stream_handle) {
        fwrite($this->stream_handle, $data);
    } else {
        $this->response_data .= $data;    // line 554
    }

    $this->response_bytes += strlen($data);
    return $data_length;
}
```

**Change — Add `stream_callback` branch:**

```diff
+ /**
+  * Stream callback for chunk-by-chunk delivery
+  *
+  * @var callable|false
+  */
+ private $stream_callback = false;

  public function stream_body($handle, $data) {
      $this->hooks->dispatch('request.progress', [$data, $this->response_bytes, $this->response_byte_limit]);
      $data_length = strlen($data);

      // Are we limiting the response size?
      if ($this->response_byte_limit) {
          if ($this->response_bytes === $this->response_byte_limit) {
              return $data_length;
          }
          if (($this->response_bytes + $data_length) > $this->response_byte_limit) {
              $limited_length = ($this->response_byte_limit - $this->response_bytes);
              $data           = substr($data, 0, $limited_length);
          }
      }

      if ($this->stream_handle) {
          fwrite($this->stream_handle, $data);
+     } elseif ($this->stream_callback) {
+         // Deliver chunk to callback; do NOT accumulate in response_data.
+         // Return false from callback to abort the transfer.
+         $result = call_user_func($this->stream_callback, $data, $this->response_bytes, $this->response_byte_limit);
+         if ($result === false) {
+             return -1; // Tells cURL to abort the transfer (CURLE_WRITE_ERROR)
+         }
      } else {
          $this->response_data .= $data;
      }

      $this->response_bytes += strlen($data);
      return $data_length;
  }
```

**Change to `request()` method** — set `stream_callback` from options (before `curl_exec()`, after line 188):

```diff
  $this->response_byte_limit = false;
  if ($options['max_bytes'] !== false) {
      $this->response_byte_limit = $options['max_bytes'];
  }

+ if (!empty($options['stream_callback']) && is_callable($options['stream_callback'])) {
+     $this->stream_callback = $options['stream_callback'];
+ } else {
+     $this->stream_callback = false;
+ }
```

**Change to `process_response()` method** (lines 470–505) — when streaming via callback, headers are already separated (cURL's `CURLOPT_HEADERFUNCTION` sends them to `stream_headers()` at line 456), so `$response` (which is `$this->response_data`) is empty:

```diff
  if ($options['filename'] !== false && $this->stream_handle) {
      fclose($this->stream_handle);
      $this->headers = trim($this->headers);
+ } elseif ($this->stream_callback) {
+     // Body was delivered via callback, not accumulated.
+     // $this->headers already contains only headers (from stream_headers()).
+     $this->headers = trim($this->headers);
  } else {
      $this->headers .= $response;
  }
```

**Why `$this->headers .= $response` is wrong for streaming:** In the normal (non-streaming) path, `$this->headers` contains the HTTP headers (from `stream_headers()`) and `$response` is `$this->response_data` (the body). They get concatenated so `parse_response()` can split them on `\r\n\r\n`. When streaming via callback, `$this->response_data` is empty, and appending it would cause `parse_response()` to throw `requests.no_crlf_separator` when it tries to find the body separator.

**Key insight about decompression:** cURL handles decompression automatically via `CURLOPT_ENCODING` set to `''` at constructor line 110. The data arriving in `stream_body()` is **already decompressed**. This is why the cURL transport is ideal for streaming — no post-processing needed.

#### 2.5 `wp-includes/Requests/src/Transport/Fsockopen.php` — Read Loop Change

**Current code** — read loop body accumulation (lines 299–345):

```php
while (!feof($socket)) {
    $this->info = stream_get_meta_data($socket);
    if ($this->info['timed_out']) {
        throw new Exception('fsocket timed out', 'timeout');
    }

    $block = fread($socket, Requests::BUFFER_SIZE);
    if (!$doingbody) {
        $response .= $block;
        if (strpos($response, "\r\n\r\n")) {
            list($headers, $block) = explode("\r\n\r\n", $response, 2);
            $doingbody             = true;
        }
    }

    // Are we in body mode now?
    if ($doingbody) {
        $options['hooks']->dispatch('request.progress', [$block, $size, $this->max_bytes]);
        $data_length = strlen($block);
        if ($this->max_bytes) {
            // ... byte limit checks ...
        }

        $size += strlen($block);
        if ($download) {
            fwrite($download, $block);
        } else {
            $body .= $block;    // line 338
        }
    }
}
```

**Change — Add `stream_callback` branch in the read loop:**

```diff
+ $stream_callback = (!empty($options['stream_callback']) && is_callable($options['stream_callback']))
+     ? $options['stream_callback']
+     : false;

  while (!feof($socket)) {
      // ... existing header/body split logic ...

      if ($doingbody) {
          $options['hooks']->dispatch('request.progress', [$block, $size, $this->max_bytes]);
          // ... byte limit checks ...

          $size += strlen($block);
          if ($download) {
              fwrite($download, $block);
+         } elseif ($stream_callback) {
+             $result = call_user_func($stream_callback, $block, $size, $this->max_bytes);
+             if ($result === false) {
+                 break; // Abort transfer
+             }
          } else {
              $body .= $block;
          }
      }
  }
```

**Change to return value** — after loop (lines 347–355):

```diff
  $this->headers = $headers;

  if ($download) {
      fclose($download);
+ } elseif ($stream_callback) {
+     // Body was delivered via callback. Return headers only.
+     $this->headers .= "\r\n\r\n";
  } else {
      $this->headers .= "\r\n\r\n" . $body;
  }
```

**Fsockopen decompression note:** Unlike cURL, fsockopen does NOT auto-decompress. Data arrives compressed. SSE/LLM APIs do not use content-encoding on long-lived event streams, so this is not a problem in practice. If a future use case requires compressed streaming via fsockopen, a streaming inflate wrapper using `inflate_init()`/`inflate_add()` (PHP 7.0+) can be added as a follow-up.

#### 2.6 `wp-includes/Requests/src/Response.php` — Add `$body_streamed` Flag

**Current code** (`Requests/src/Response.php`):

```php
public $body = '';
```

**Change — Add streamed flag:**

```diff
  public $body = '';

+ /**
+  * Whether the body was delivered via streaming callback.
+  * When true, $body is empty and was consumed chunk-by-chunk during transport.
+  *
+  * @var bool
+  */
+ public $body_streamed = false;
```

**Set in `Requests::parse_response()`** (alongside the `stream_callback` guard):

```diff
+ if ($options['stream_callback']) {
+     $return->body_streamed = true;
+ }
```

**Why:** Allows downstream code to distinguish "body is empty because the response had no body" from "body is empty because it was streamed." `WP_HTTP_Requests_Response::to_array()` can optionally expose this.

### 3. SSE Parser Class — `WP_SSE_Parser`

A standalone parser for Server-Sent Events that works with chunked data (chunks may split across SSE event boundaries).

```php
/**
 * Server-Sent Events parser for streaming HTTP responses.
 *
 * Handles chunked data where SSE events may span multiple chunks.
 * Designed as the `on_data` callback for wp_remote_post() streaming.
 *
 * @since 6.x.0
 */
class WP_SSE_Parser {

    /** @var string Buffer for incomplete events */
    private $buffer = '';

    /** @var callable Callback receiving parsed events: function( WP_SSE_Event $event ) */
    private $event_callback;

    /** @var string Default event type per SSE spec */
    private $last_event_type = 'message';

    /** @var string Last event ID for reconnection */
    private $last_event_id = '';

    /**
     * @param callable $event_callback Called for each complete SSE event.
     *                                 Receives a WP_SSE_Event object.
     */
    public function __construct( callable $event_callback ) {
        $this->event_callback = $event_callback;
    }

    /**
     * Feed a chunk of data from the HTTP stream.
     *
     * Intended as the `on_data` callback:
     *   'on_data' => [ $parser, 'feed' ]
     *
     * @param string   $chunk      Raw chunk from the transport.
     * @param int      $bytes_so_far Total bytes received.
     * @param int|bool $max_bytes  Byte limit or false.
     * @return bool|void Return false to abort the stream.
     */
    public function feed( string $chunk, int $bytes_so_far, $max_bytes ) {
        $this->buffer .= $chunk;

        // SSE events are separated by blank lines (\n\n).
        while ( ( $pos = strpos( $this->buffer, "\n\n" ) ) !== false ) {
            $raw_event    = substr( $this->buffer, 0, $pos );
            $this->buffer = substr( $this->buffer, $pos + 2 );

            $event = $this->parse_event( $raw_event );
            if ( $event !== null ) {
                $result = call_user_func( $this->event_callback, $event );
                if ( $result === false ) {
                    return false; // Abort stream
                }
            }
        }
    }

    /**
     * Parse a single raw SSE event block into a WP_SSE_Event.
     *
     * @param string $raw_event Raw event text (lines separated by \n).
     * @return WP_SSE_Event|null Null for comment-only blocks.
     */
    private function parse_event( string $raw_event ): ?WP_SSE_Event {
        $lines = explode( "\n", $raw_event );
        $data  = [];
        $event = $this->last_event_type;
        $id    = $this->last_event_id;

        foreach ( $lines as $line ) {
            if ( $line === '' || $line[0] === ':' ) {
                continue; // Comment or empty
            }

            $colon = strpos( $line, ':' );
            if ( $colon === false ) {
                $field = $line;
                $value = '';
            } else {
                $field = substr( $line, 0, $colon );
                $value = substr( $line, $colon + 1 );
                if ( isset( $value[0] ) && $value[0] === ' ' ) {
                    $value = substr( $value, 1 );
                }
            }

            switch ( $field ) {
                case 'data':
                    $data[] = $value;
                    break;
                case 'event':
                    $event = $value;
                    break;
                case 'id':
                    if ( strpos( $value, "\0" ) === false ) {
                        $id                  = $value;
                        $this->last_event_id = $value;
                    }
                    break;
                case 'retry':
                    // Ignored for now — no reconnection logic in a callback-based parser.
                    break;
            }
        }

        if ( empty( $data ) ) {
            return null;
        }

        $this->last_event_type = 'message'; // Reset per SSE spec.

        return new WP_SSE_Event(
            implode( "\n", $data ),
            $event,
            $id
        );
    }

    /**
     * Get the last event ID (for reconnection).
     *
     * @return string
     */
    public function get_last_event_id(): string {
        return $this->last_event_id;
    }
}

/**
 * Represents a single Server-Sent Event.
 *
 * @since 6.x.0
 */
class WP_SSE_Event {
    /** @var string Event data (may contain newlines) */
    public $data;

    /** @var string Event type (default: 'message') */
    public $type;

    /** @var string Event ID */
    public $id;

    public function __construct( string $data, string $type = 'message', string $id = '' ) {
        $this->data = $data;
        $this->type = $type;
        $this->id   = $id;
    }

    /**
     * Decode event data as JSON.
     *
     * @param bool $assoc Return associative array. Default true.
     * @return mixed Decoded data.
     * @throws \JsonException On invalid JSON (PHP 7.3+).
     */
    public function json( bool $assoc = true ) {
        return json_decode( $this->data, $assoc, 512, JSON_THROW_ON_ERROR );
    }
}
```

**File location:** `wp-includes/class-wp-sse-parser.php` (loaded conditionally or autoloaded).

### 4. Usage Examples

#### 4.1 Basic Chunk Callback

```php
$collected = '';

$response = wp_remote_post( 'https://api.example.com/generate', array(
    'headers' => array( 'Authorization' => 'Bearer sk-...' ),
    'body'    => wp_json_encode( array( 'prompt' => 'Hello' ) ),
    'timeout' => 120,
    'stream'  => true,
    'on_data' => function( $chunk, $bytes_so_far, $max_bytes ) use ( &$collected ) {
        $collected .= $chunk;
        // Process each chunk as it arrives
        error_log( "Received chunk: " . strlen( $chunk ) . " bytes" );
    },
) );

// $response['body'] is '' — data was delivered via callback
// $response['response']['code'] is still 200, headers are available
$status = wp_remote_retrieve_response_code( $response );
```

#### 4.2 SSE from an LLM API

```php
$tokens = [];
$parser = new WP_SSE_Parser( function( WP_SSE_Event $event ) use ( &$tokens ) {
    if ( $event->data === '[DONE]' ) {
        return; // Stream complete
    }

    $payload = $event->json();
    $token   = $payload['choices'][0]['delta']['content'] ?? '';
    if ( $token !== '' ) {
        $tokens[] = $token;
        // Could echo + flush for real-time output
    }
} );

$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
    'headers' => array(
        'Authorization' => 'Bearer sk-...',
        'Content-Type'  => 'application/json',
    ),
    'body'    => wp_json_encode( array(
        'model'    => 'gpt-4',
        'stream'   => true,
        'messages' => array(
            array( 'role' => 'user', 'content' => 'Say hello' ),
        ),
    ) ),
    'timeout' => 120,
    'stream'  => true,
    'on_data' => array( $parser, 'feed' ),
) );

$full_response = implode( '', $tokens );
```

#### 4.3 File Streaming (Existing, Unchanged)

```php
// This behavior is 100% unchanged:
$response = wp_remote_get( 'https://example.com/large-file.zip', array(
    'stream'   => true,
    'filename' => '/tmp/large-file.zip',
) );

// Body is empty, file is at /tmp/large-file.zip
$filename = $response['filename'];
```

#### 4.4 Error Handling During Streaming

```php
$response = wp_remote_post( $url, array(
    'timeout' => 120,
    'stream'  => true,
    'on_data' => function( $chunk, $bytes, $limit ) {
        // Return false to abort the transfer
        if ( strlen( $chunk ) > 1048576 ) {
            error_log( 'Chunk too large, aborting' );
            return false;
        }

        // Exceptions in the callback will propagate up through curl_exec()
        // and be caught by WP_Http::request()'s try/catch as a WP_Error.
        process_chunk( $chunk );
    },
) );

if ( is_wp_error( $response ) ) {
    // cURL will report CURLE_WRITE_ERROR if callback returned false
    // Or the exception message if one was thrown
    $error = $response->get_error_message();
}

// HTTP errors (4xx/5xx) are still detectable via status code:
$code = wp_remote_retrieve_response_code( $response );
if ( $code >= 400 ) {
    // Note: with streaming, the error body was already delivered to the callback.
    // The callback should handle non-200 responses itself.
}
```

### 5. Backward Compatibility

This implementation is **fully backward-compatible**:

1. **New optional args with safe defaults:** `'on_data' => null` (WP_Http) and `'stream_callback' => false` (Requests) default to their inactive states. Existing code never sets these, so behavior is unchanged.

2. **`'stream' => true` still required:** The `on_data` callback is only wired up when `'stream' => true` is set (line 355 guard in `class-wp-http.php`). Without it, `on_data` is ignored — no accidental streaming.

3. **Existing `'filename'` path untouched:** The file-streaming path (`$this->stream_handle` in Curl, `$download` in Fsockopen) remains the first branch checked. `stream_callback` is an `elseif` — it only activates when no file handle is open.

4. **Response array structure unchanged:** `to_array()` still returns `headers`, `body`, `response`, `cookies`, `filename`. The `body` key is an empty string when streaming (same as file streaming today). The new `body_streamed` flag on `Response` is additive.

5. **No removed hooks or filters:** All existing hooks (`request.progress`, `http_request_args`, `pre_http_request`, `http_response`, etc.) continue to fire at the same points. The `request.progress` hook still fires even when `stream_callback` is set (it fires first, at the top of `stream_body()`).

6. **`OPTION_DEFAULTS` is a const:** Adding a key to this array in the Requests library is additive and doesn't affect existing consumers who iterate over it.

7. **Return `-1` from cURL write callback:** This is the documented way to abort a cURL transfer. It triggers `CURLE_WRITE_ERROR`, which is already handled in `Curl::request()` at lines 207–217 (the retry-with-no-encoding block). The retry will also get `-1` if the callback still returns false, so the error will propagate correctly.

### 6. Addressing Felix Arntz's Concerns

This section directly addresses the concerns raised in [WordPress/wp-ai-client#11](https://github.com/WordPress/wp-ai-client/issues/11).

#### "The Requests library has barely any active maintenance"

This proposal minimizes Requests library changes to **3 files, ~38 lines total**:

| File | Changes |
|------|---------|
| `Requests.php` | Add `'stream_callback' => false` to `OPTION_DEFAULTS`. Add 2 guard clauses in `parse_response()`. |
| `Transport/Curl.php` | Add 1 property, 1 `elseif` branch in `stream_body()`, 3 lines in `request()`, 3 lines in `process_response()`. |
| `Transport/Fsockopen.php` | Add 1 variable, 1 `elseif` branch in the read loop, 2 lines in the return section. |
| `Response.php` | Add 1 boolean property (`$body_streamed`). |

Every change follows existing patterns. The `stream_callback` branch in `stream_body()` mirrors the existing `$this->stream_handle` branch. The `parse_response()` guards mirror the existing `!$options['filename']` guard. No new methods, no new classes, no architectural changes to the Requests library.

#### "Subject to several bottlenecks"

Eight bottlenecks were identified. Here is how each is resolved:

| Bottleneck | Resolution |
|------------|------------|
| `Curl::stream_body()` accumulates body in `$this->response_data` (line 554) | New `elseif` branch calls `stream_callback` instead of concatenating |
| `Fsockopen::request()` accumulates body in `$body` (line 338) | New `elseif` branch calls `stream_callback` instead of concatenating |
| `Curl::process_response()` concatenates headers+body (line 481) | New branch for `stream_callback`: trim headers only, skip concatenation |
| `Requests::parse_response()` extracts body from raw string (line 733) | Guard: `!$options['stream_callback']` skips body extraction |
| `Requests::parse_response()` decodes chunked encoding (line 771) | Guard: `!$options['stream_callback']` skips decode |
| `Requests::parse_response()` decompresses body (line 777) | Guard: `!$options['stream_callback']` skips decompress. cURL already decompresses in-flight via `CURLOPT_ENCODING` (line 110). |
| `WP_HTTP_Requests_Response::to_array()` returns body as string | No change needed — body is `''` (same as file streaming). Data already delivered via callback. |
| `WP_Http::request()` returns flat array with string body | No change needed — same as above. |

#### "Duplicating quite a bit of WordPress Core code"

Felix's `ai-services` plugin requires Guzzle and duplicates WP Core HTTP code because `WP_Http::request()` has no callback-based streaming. His `HTTP` base class re-implements:
- `pre_http_request` filter checking
- Proxy settings (`WP_HTTP_Proxy`)
- SSL certificate paths
- User-agent defaults
- Timeout defaults

With native `on_data` support, **all of this is unnecessary**. A plugin calls `wp_remote_post()` with `'stream' => true, 'on_data' => callable` and gets proxy, SSL, cookies, user-agent, and all hooks for free — through the same code path as every other HTTP request. Guzzle is no longer needed.

**Before (ai-services approach):**
```php
// ~200 lines of duplicated WP Core HTTP logic
// + Guzzle dependency
// + PSR-7 stream wrapper
$http = new HTTP_With_Streams();
$stream = $http->request( $url, $args ); // Custom implementation
```

**After (native support):**
```php
// 0 lines of duplicated code, 0 new dependencies
$response = wp_remote_post( $url, array(
    'timeout' => 120,
    'stream'  => true,
    'on_data' => array( $parser, 'feed' ),
) );
```

#### Complexity Estimate

| Component | Lines Changed | Lines Added | Effort |
|-----------|---------------|-------------|--------|
| `class-wp-http.php` — defaults + stream guard + option passing | 4 | 7 | Trivial |
| `Requests.php` — `OPTION_DEFAULTS` + `parse_response()` guards | 4 | 4 | Trivial |
| `Transport/Curl.php` — property + `stream_body()` + `request()` + `process_response()` | 3 | 18 | Small |
| `Transport/Fsockopen.php` — variable + read loop + return | 2 | 12 | Small |
| `Response.php` — `$body_streamed` flag | 0 | 8 | Trivial |
| `class-wp-sse-parser.php` (new file) | 0 | ~150 | Medium (self-contained) |
| **Total** | **~13** | **~199** | **1–2 days** |

The core streaming mechanism is ~50 lines across 4 existing files. The SSE parser is a standalone new class. No existing tests break. No API contracts change.

### 7. Test Plan

#### Unit Tests

| Test | Description |
|------|-------------|
| `test_stream_callback_receives_chunks` | POST to httpbin.org/post, verify callback is called with non-empty chunks |
| `test_stream_callback_body_empty` | Verify `$response['body']` is `''` when `on_data` is used |
| `test_stream_callback_headers_available` | Verify headers and status code are correct in response |
| `test_stream_callback_abort` | Return `false` from callback, verify transfer stops (cURL: `CURLE_WRITE_ERROR`) |
| `test_stream_callback_not_called_without_stream_flag` | Set `on_data` without `stream => true`, verify callback is NOT invoked |
| `test_file_streaming_unchanged` | Existing `stream + filename` test still passes |
| `test_stream_callback_with_max_bytes` | Verify byte limiting still works with callbacks |
| `test_stream_callback_with_redirects` | Verify callbacks handle 301/302 redirects correctly |
| `test_body_streamed_flag` | Verify `Response::$body_streamed` is `true` when callback used, `false` otherwise |
| `test_on_data_ignored_when_not_callable` | Pass non-callable `on_data`, verify no error and normal behavior |

#### SSE Parser Tests

| Test | Description |
|------|-------------|
| `test_sse_single_event` | Feed `"data: hello\n\n"`, verify event with data `"hello"` |
| `test_sse_multi_line_data` | Feed `"data: line1\ndata: line2\n\n"`, verify data is `"line1\nline2"` |
| `test_sse_split_across_chunks` | Feed `"data: hel"` then `"lo\n\n"`, verify single event |
| `test_sse_event_type` | Feed `"event: custom\ndata: x\n\n"`, verify `$event->type === 'custom'` |
| `test_sse_event_id` | Feed `"id: 42\ndata: x\n\n"`, verify `$event->id === '42'` |
| `test_sse_comments_ignored` | Feed `": comment\ndata: x\n\n"`, verify data is `"x"` |
| `test_sse_json_decode` | Feed `"data: {\"key\":\"val\"}\n\n"`, verify `$event->json()` works |
| `test_sse_done_signal` | Feed `"data: [DONE]\n\n"`, verify event with data `"[DONE]"` |
| `test_sse_abort_from_callback` | Return `false` from event callback, verify `feed()` returns `false` |

#### Integration Tests

| Test | Description |
|------|-------------|
| `test_curl_transport_streaming` | Force cURL transport, verify chunk delivery |
| `test_fsockopen_transport_streaming` | Force fsockopen transport, verify chunk delivery |
| `test_sse_with_real_endpoint` | Stream from a local SSE test server, verify complete event parsing |
| `test_streaming_timeout` | Verify `timeout` is respected during streaming (default 5s is too low for SSE — tests should set 120s) |
| `test_streaming_ssl` | Verify SSL verification still works during streaming |

### 8. WP_HTTP_Requests_Hooks Bridge

The `WP_HTTP_Requests_Hooks` class (in `class-wp-http-requests-hooks.php`) bridges Requests internal hooks to WordPress actions. Its `dispatch()` method fires `do_action_ref_array("requests-{$hook}", ...)` for every Requests hook, meaning `request.progress` is already surfaced as the WordPress action `requests-request.progress`.

This means plugin authors can already listen to chunk data via:

```php
add_action( 'requests-request.progress', function( $data, $bytes_so_far, $limit ) {
    // Process chunk
}, 10, 3 );
```

However, this hook fires for ALL HTTP requests, not just streaming ones, and the data is still accumulated in the transport. The `on_data` callback is per-request and prevents accumulation, making it the correct API for streaming.

---

## REST API Outbound Streaming — Serving SSE Responses

The previous sections analyze **inbound** streaming (WordPress calling an LLM API and receiving chunks). This section analyzes the **outbound** side: how `WP_REST_Server` serves responses to clients, and what would need to change to support streaming/SSE responses from REST API endpoints.

### How `WP_REST_Server::serve_request()` Currently Works

**Source:** `wp-includes/rest-api/class-wp-rest-server.php`, `serve_request()` method starting at line 285.

The response pipeline is fundamentally **batch-oriented** — the entire response is assembled in memory, JSON-encoded, and echoed in a single operation:

```
serve_request($path)
  ├─ Build WP_REST_Request from $_GET, $_POST, $_FILES, headers, body
  ├─ $result = $this->check_authentication()
  ├─ $result = $this->dispatch($request)                    // Route to handler
  ├─ $result = rest_ensure_response($result)                // Normalize to WP_REST_Response
  ├─ apply_filters('rest_post_dispatch', $result, ...)       // Post-dispatch filter
  ├─ Optional: $this->envelope_response($result, $embed)    // ?_envelope wrapping
  ├─ $this->send_headers($result->get_headers())            // Send all headers
  ├─ $this->set_status($code)                               // Send status code
  ├─ apply_filters('rest_pre_serve_request', false, ...)     // Short-circuit hook
  │
  └─ If not served:
       ├─ $result = $this->response_to_data($result, $embed)  // Convert to array + embed links
       ├─ apply_filters('rest_pre_echo_response', $result, ...)
       ├─ $result = wp_json_encode($result, $options)          // JSON-encode entire response
       └─ echo $result                                          // Single echo of complete JSON
```

### Specific Bottlenecks for SSE/Streaming

**1. `WP_REST_Response` is a complete data container**

`rest_ensure_response()` converts everything to `WP_REST_Response`, which extends `WP_HTTP_Response`. The response data is a single value (`->data`) — typically an array or object. There's no concept of incremental data.

**2. `response_to_data()` materializes the full response (line 524)**

Called at line 524 in `serve_request()`: `$result = $this->response_to_data( $result, $embed )`. This method (defined at line 588) recursively processes the response data, embeds linked resources (`_embedded`), and returns a plain array. This is a synchronous, all-at-once operation.

**3. `wp_json_encode()` requires the complete data (line 545)**

```php
$result = wp_json_encode( $result, $this->get_json_encode_options( $request ) );
```

JSON encoding requires the entire data structure. You can't incrementally JSON-encode a partially-available array.

**4. Single `echo` output (line 566)**

```php
if ( $jsonp_callback ) {
    echo '/**/' . $jsonp_callback . '(' . $result . ')';
} else {
    echo $result;
}
```

One `echo` call with the complete JSON string. No flushing, no chunked output.

**5. Content-Type defaults to JSON or JavaScript (line 317-318)**

```php
$content_type = ( $jsonp_callback && $jsonp_enabled ) ? 'application/javascript' : 'application/json';
$this->send_header( 'Content-Type', $content_type . '; charset=' . get_option( 'blog_charset' ) );
```

The Content-Type is determined before routing: `application/json` for standard requests, `application/javascript` when JSONP is active. Neither supports SSE (`text/event-stream`). This header is sent **before** the endpoint handler runs, so by the time a streaming handler executes, the wrong Content-Type is already sent.

**6. No output buffering control**

PHP's output buffering and web server buffering (nginx's `proxy_buffering`, Apache's `mod_deflate`) are not addressed. SSE requires disabling all buffering layers.

### The `rest_pre_serve_request` Escape Hatch

**This filter (line 515) is the most viable insertion point today:**

```php
$served = apply_filters( 'rest_pre_serve_request', false, $result, $request, $this );

if ( ! $served ) {
    // ... normal JSON echo path (lines 524-566) ...
}
```

If a filter returns `true`, the normal response path is skipped entirely. A streaming endpoint could:

1. Register a `rest_pre_serve_request` filter
2. Check if the current route is a streaming endpoint
3. Override headers (Content-Type to `text/event-stream`)
4. Manage its own output loop
5. Return `true` to prevent the default JSON echo

**Problem:** The `$result` parameter is already a fully-materialized `WP_REST_Response` at this point. The dispatch has completed. For true streaming, the endpoint handler itself needs to stream — but `dispatch()` expects a return value (`WP_REST_Response`).

### What Would Need to Change

**Required changes to `serve_request()`:**

**1. Defer Content-Type header (line 318)**

Move the Content-Type header to after dispatch, so streaming endpoints can set their own:

```diff
- $content_type = ( $jsonp_callback && $jsonp_enabled ) ? 'application/javascript' : 'application/json';
- $this->send_header( 'Content-Type', $content_type . '; charset=' . get_option( 'blog_charset' ) );
+ // Content-Type deferred until after dispatch to allow streaming endpoints to override
```

Then after dispatch, before sending headers:

```php
if ( ! $result->get_headers()['Content-Type'] ) {
    $content_type = ( $jsonp_callback && $jsonp_enabled ) ? 'application/javascript' : 'application/json';
    $result->header( 'Content-Type', $content_type . '; charset=' . get_option( 'blog_charset' ) );
}
```

**2. New `WP_REST_Streaming_Response` class**

```php
class WP_REST_Streaming_Response extends WP_REST_Response {
    /** @var callable Generator function that yields SSE events */
    private $stream_callback;

    public function __construct( callable $stream_callback, int $status = 200 ) {
        parent::__construct( null, $status );
        $this->stream_callback = $stream_callback;
        $this->header( 'Content-Type', 'text/event-stream' );
        $this->header( 'Cache-Control', 'no-cache' );
        $this->header( 'X-Accel-Buffering', 'no' );
    }

    public function get_stream_callback(): callable {
        return $this->stream_callback;
    }
}
```

**3. Handle streaming responses in `serve_request()` (before line 515)**

After headers are sent and before the `rest_pre_serve_request` filter:

```php
if ( $result instanceof WP_REST_Streaming_Response ) {
    // Disable output buffering
    while ( ob_get_level() ) {
        ob_end_clean();
    }

    // Execute the streaming callback
    $callback = $result->get_stream_callback();
    $callback( $request );

    return null;
}
```

**4. Endpoint registration example:**

```php
register_rest_route( 'myplugin/v1', '/chat', [
    'methods'  => 'POST',
    'callback' => function( WP_REST_Request $request ) {
        return new WP_REST_Streaming_Response( function( $request ) {
            $parser = new WP_SSE_Parser( function( WP_SSE_Event $event ) {
                // Re-emit to client
                echo "data: " . $event->data . "\n\n";
                flush();
            } );

            wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
                'headers' => [ 'Authorization' => 'Bearer ' . OPENAI_KEY ],
                'body'    => wp_json_encode( $request->get_json_params() ),
                'timeout' => 120,
                'stream'  => true,
                'on_data' => [ $parser, 'feed' ],
            ] );

            echo "data: [DONE]\n\n";
            flush();
        } );
    },
    'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
] );
```

### Integration: Both Halves Together

The complete streaming picture connects the two halves:

```
Client (browser)                    WordPress                         LLM API
     │                                  │                                │
     │  POST /wp-json/plugin/v1/chat    │                                │
     │ ──────────────────────────────►   │                                │
     │                                  │  POST /v1/chat/completions     │
     │                                  │  stream: true, on_data: cb     │
     │                                  │ ──────────────────────────────► │
     │                                  │                                │
     │                                  │  ◄── data: {"token": "Hel"}    │
     │  ◄── data: {"token": "Hel"}      │      (via stream_callback)     │
     │      (SSE event)                 │                                │
     │                                  │  ◄── data: {"token": "lo"}     │
     │  ◄── data: {"token": "lo"}       │                                │
     │                                  │                                │
     │                                  │  ◄── data: [DONE]              │
     │  ◄── data: [DONE]               │                                │
     │                                  │                                │
```

**Inbound streaming**: `wp_remote_post()` with `'stream' => true, 'on_data' => callable` delivers LLM response chunks to a callback via the `stream_callback` option in the Requests transports.

**Outbound streaming** (this section): `WP_REST_Streaming_Response` bypasses the JSON encode + single echo path, instead executing a callback that can `echo` + `flush()` SSE events incrementally.

**The `on_data` callback bridges the two:** It receives chunks from the inbound LLM stream and immediately echoes them as SSE events to the waiting client. WordPress becomes a streaming proxy with authentication, permission checking, and hook integration intact.

### Buffering Considerations

For SSE to work end-to-end, **all buffering layers** must be disabled:

| Layer | Default | Required for SSE | How to Disable |
|-------|---------|------------------|----------------|
| PHP output buffering | Often enabled (`output_buffering = 4096`) | Off | `while (ob_get_level()) ob_end_clean()` |
| PHP implicit flush | Off | On | `ini_set('implicit_flush', 1)` or `ob_implicit_flush(1)` |
| nginx `proxy_buffering` | On | Off | `X-Accel-Buffering: no` header |
| nginx `fastcgi_buffering` | On | Off | `X-Accel-Buffering: no` header |
| Apache `mod_deflate` | Compresses text/* | Must exclude `text/event-stream` | `SetEnvIfNoCase Content-Type text/event-stream no-gzip` |
| PHP `zlib.output_compression` | May be on | Off | `ini_set('zlib.output_compression', 0)` |

The `WP_REST_Streaming_Response` class should handle PHP-level buffering automatically. Server-level buffering (nginx/Apache) requires the `X-Accel-Buffering: no` header, which is set by the response class.

### Summary: REST API Streaming Feasibility

| Approach | Core Changes | Works Today | SSE Quality |
|----------|-------------|-------------|-------------|
| `rest_pre_serve_request` filter hack | None | ✅ Yes | Mediocre — fights the framework |
| `WP_REST_Streaming_Response` class | ~50 lines in `serve_request()` + new class | ❌ Needs core patch | Good — clean integration |
| Combined with inbound `on_data` | Both HTTP API + REST Server changes | ❌ Needs core patches | Excellent — full pipeline |

**The architectural barrier is the same as the HTTP API:** WordPress's REST server was designed around a request→response model where the response is a complete data structure. Every step — dispatch returning a value, `response_to_data()` materializing embedded resources, `wp_json_encode()` serializing the whole thing, single `echo` — assumes the response is finished before output begins.

The `rest_pre_serve_request` filter is the pragmatic escape hatch. A `WP_REST_Streaming_Response` class would be the clean solution. Combined with the inbound `on_data` streaming option, WordPress could serve as a proper streaming proxy for LLM APIs — with authentication, permissions, and hooks intact.
