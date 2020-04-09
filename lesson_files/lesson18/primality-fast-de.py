def isItPrime(N): # Name und Parameter wie vorher
  for D in range(2, N):
    if (D * D > N):          # erste neue Zeile: D als Teiler zu groß?
      break                  # zweite neue Zeile: Schleife verlassen.
    if N % D == 0:
      print(N, "ist nicht prim; teilbar durch", D)
      return
  print(N, "ist eine Primzahl")

isItPrime(1000006000009)
isItPrime(1666666009999)
