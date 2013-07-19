def isItPrime(N):
  for D in range(2, N):                        # teste D von 2 bis N-1
    if N % D == 0:                             # ist D ein Teiler von N?
      print(N, "ist nicht prim; teilbar durch", D)
      return
  print(N, "ist eine Primzahl")                # keine Teiler gefunden
