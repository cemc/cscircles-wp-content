M = 1000000
isPrime = [False]*2 + [True]*(M-1)
for i in range(2, M+1):
    if isPrime[i]:
        for j in range(2, 1+M//i):
            isPrime[i*j] = False
