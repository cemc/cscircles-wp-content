def testePremier(N):
  for D in range(2, N):                        # essai D de 2 à N-1
    if N % D == 0:                             # D divise-t-il N?
      print(N, "n'est pas premier; divisible par", D)
      return
  print(N, "est premier")                      # rien diviseurs trouve
