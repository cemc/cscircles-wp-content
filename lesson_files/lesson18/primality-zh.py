def isItPrime(N):
  for D in range(2, N):                        # 测试 D 从 2 到 N-1
    if N % D == 0:                             # D是N的除数么?
      print(N, "is not prime; divisible by", D)
      return
  print(N, "is prime")                         # 没有除数
