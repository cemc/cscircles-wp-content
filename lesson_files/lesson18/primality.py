def isItPrime(N):
  for D in range(2, N):                        # test D from 2 to N-1
    if N % D == 0:                             # is D a divisor of N?
      print(N, "is not prime; divisible by", D)
      return
  print(N, "is prime")                         # there were no divisors
