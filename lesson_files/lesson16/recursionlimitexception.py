import sys
def addsquares(n):
  if n == 0: return 0
  return addsquares(n - 1) + n**2
try:
 print(addsquares(1000))
except:
    print ("Unexpected error:", sys.exc_info())
