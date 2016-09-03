def isItPrime(N):             # 与原来一样
  for D in range(2, N):
    if N % D == 0:       
      print(N, "不是质数; 可以被整除的数为", D)
      return
  print(N, "是质数") 
  
isItPrime(324635459)
