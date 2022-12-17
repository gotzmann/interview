-- Benchmarking HTTP POST script

wrk.method = "POST"
wrk.body = '{"brand": "Trek"}'
wrk.headers["Content-Type"] = "application/json"
