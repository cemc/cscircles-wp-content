def f():
    from math import sqrt
    M = 500000
    try:
        isPrime
    except NameError:
        return "NisPrime was not defined."
    if (type(isPrime) != type([])):
        return "NType of isPrime was changed. Should be a list."
    if (len(isPrime) != M+1):
        return "NLength of isPrime was changed. Should be "+str(M+1)+"."
    for i in range(0, M+1):
        if (type(isPrime[i]) != type(True)):
            return "NisPrime["+str(i)+"] should be of boolean type, found "+str(type(isPrime[i]))
    if (isPrime[0] != False):
        return "NisPrime[0] should be False, found True"
    if (isPrime[1] != False):
        return "NisPrime[1] should be False, found True"

    check = [False]*2 + [True]*(M-1)
    for i in range(2, 2+int(sqrt(M))):
        if (check[i] != isPrime[i]):
            return "NisPrime["+str(i)+"] should be "+str(check[i])+", found "+str(isPrime[i])
        for j in range(2, 1+M//i):
            check[i*j] = False

    return "YAll values from isPrime[0] to isPrime["+str(M)+"] are correct!"

print(chr(0)+f())
