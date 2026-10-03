import urllib.request
import urllib.error
import os

class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
   def redirect_request(self, req, fp, code, msg, headers, newurl):
      return None

opener = urllib.request.build_opener(NoRedirectHandler)


req = urllib.request.Request(
"http://104.20.23.154/",
headers={
"Host": "example.com",
"User-Agent": "Python-Test/1.0",
}
)

try:
   response = opener.open(req)

   print("Status:", response.status)
   print("Headers:")
   print(response.headers)
   print("Body:")
   print(response.read().decode("utf-8", errors="replace"))

except urllib.error.HTTPError as e:
   print("Status:", e.code)
   print("Headers:")
   print(e.headers)

   location = e.headers.get("Location")
   if location:
      print("Redirect Location:", location)

   print("Body:")
