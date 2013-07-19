def isItPrime(N):   # Name und Parameter wie vorher
  for D in range(2, N):
    if N % D == 0:       
      print(N, "ist nicht prim; teilbar durch", D)
      return
  print(N, "ist eine Primzahl") 
  
isItPrime(324635459)
