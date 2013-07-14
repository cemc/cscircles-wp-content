def isItPrime(N):
  for D in range(2, N):                        # Teste D aus 2 - N-1
    if N % D == 0:                             # ist D ein Teiler von N?
      print(N, "is not prime; divisible by", D)
      return
  print(N, "is prime")                         # es gibt keine Teiler
