L = ['text', 11]
LAgain = L             # inne odwołanie do L
print(LAgain is L)     # są tym samym? Tak
LCopy = L[:]           # tworzenie kopii
print(LCopy == LAgain) # te same wartości?   Tak, oba są ['text', 11]
print(LCopy is LAgain) # są tym samym? Nie: LAgain jest L, ale LCopy nie jest L
