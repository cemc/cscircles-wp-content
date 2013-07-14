def isItPrime(N):             # same as before
  for D in range(2, N):
    if N % D == 0:       
      print(N, "is not prime; divisible by", D)
      return
  print(N, "is prime") 
  
isItPrime(324635459)
