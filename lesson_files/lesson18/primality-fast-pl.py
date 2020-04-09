def isItPrime(N): # same as before
  for D in range(2, N):
    if (D * D > N):          # pierwsza dodana linia
      break                  # druga dodana linia
    if N % D == 0:
      print(N, "is not prime; divisible by", D)
      return
  print(N, "is prime")

isItPrime(1000006000009)
isItPrime(1666666009999)
