def isItPrime(N): # 和之前的一样
  for D in range(2, N):
    if (D * D > N):          # 被加上的第一行
      break                  # 被架上的第二行
    if N % D == 0:
      print(N, "is not prime; divisible by", D)
      return
  print(N, "is prime")

isItPrime(1000006000009)
isItPrime(1666666009999)
