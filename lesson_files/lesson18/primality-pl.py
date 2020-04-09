def isItPrime(N):
  for D in range(2, N):                        # test D od 2 do N-1
    if N % D == 0:                             # D jest dzielnikiem N?
      print(N, "nie jest pierwsza; podzielna przez", D)
      return
  print(N, "jest pierwsza")                         # nie ma dzielników
