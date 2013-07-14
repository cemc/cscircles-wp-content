def testePremier(N):
  for D in range(2, N):                        
    if (D * D > N):          # première ligne nouvelle
      break                  # deuxième ligne nouvelle
    if N % D == 0:                             
      print(N, "n'est pas premier; divisible par", D)
      return
  print(N, "est premier")                      

testePremier(1000006000009)
testePremier(1666666009999)
