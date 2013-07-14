def addsquares(n):
  if n == 0: return 0
  return addsquares(n - 1) + n**2
print(addsquares(1000))
