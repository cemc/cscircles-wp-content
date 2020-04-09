def f():
    from math import sqrt
    M = 500000
    try:
        isPrime
    except NameError:
        return "NisPrime ist unbekannt."
    if (type(isPrime) != type([])):
        return "NTyp von isPrime geändert. Sollte eine Liste sein."
    if (len(isPrime) != M+1):
        return "NLänge von isPrime geändert. Sollte "+str(M+1)+" sein."
    for i in range(0, M+1):
        if (type(isPrime[i]) != type(True)):
            return "NisPrime["+str(i)+"] sollte den Typ Boolean haben, hat aber den Typ "+str(type(isPrime[i]))
    if (isPrime[0] != False):
        return "NisPrime[0] sollte False sein, ist aber True"
    if (isPrime[1] != False):
        return "NisPrime[1] sollte False sein, ist aber True"

    check = [False]*2 + [True]*(M-1)
    for i in range(2, 2+int(sqrt(M))):
        if (check[i] != isPrime[i]):
            return "NisPrime["+str(i)+"] sollte "+str(check[i])+" sein, ist aber "+str(isPrime[i])
        for j in range(2, 1+M//i):
            check[i*j] = False

    return "YAlle Werte von isPrime[0] bis isPrime["+str(M)+"] sind korrekt!"

print(chr(0)+f())
