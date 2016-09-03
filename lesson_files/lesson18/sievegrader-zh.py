def f():
    from math import sqrt
    M = 500000
    try:
        isPrime
    except NameError:
        return "NisPrime 没有被定义。"
    if (type(isPrime) != type([])):
        return "NType of isPrime 被改变了。应该是一个列表。"
    if (len(isPrime) != M+1):
        return "NLength of isPrime 被改变了。 应该是 "+str(M+1)+"."
    for i in range(0, M+1):
        if (type(isPrime[i]) != type(True)):
            return "NisPrime["+str(i)+"] 应该是 boolean 类型, 找到为 "+str(type(isPrime[i]))
    if (isPrime[0] != False):
        return "NisPrime[0] 应该为 False, 找到为 True"
    if (isPrime[1] != False):
        return "NisPrime[1] 应该为 False, 找到为 True"

    check = [False]*2 + [True]*(M-1)
    for i in range(2, 2+int(sqrt(M))):
        if (check[i] != isPrime[i]):
            return "NisPrime["+str(i)+"] 应该是 "+str(check[i])+", 找到为 "+str(isPrime[i])
        for j in range(2, 1+M//i):
            check[i*j] = False

    return "你所有的值从 isPrime[0] 到 isPrime["+str(M)+"] 是正确的!"

print(chr(0)+f())
