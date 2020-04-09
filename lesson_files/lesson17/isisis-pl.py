# dlaczego "is" jest podstępne dla łańcuchów?
print("foo" is "foo", "foo" is "fo"+"o") # oba True, właściwy łańcuchów internalizowanych (internowanych)
print("e"*32 is "ee"*16) # False, długie łańcuchy nie są internalizowane
A = "foo"
B = "foofoo"
A *= 2
print(A == B, A is B) # True False: *= nie re-internuje łańcucha

# dlaczego  "is"  jest podstępne dla liczb
print(1+1 is 2) # True, ale to zachowanie wystepuje tylko dla małych liczb Integer
print(10**3 is 1000) # False
print(1.5 is 1.5, 1.5 is 0.5*3) # True, False
print(float('NaN')==float('NaN'), float('NaN') is float('NaN')) # oba False
x = float('NaN')
print(x is x, x == x) # True False; jeden z kilku przykładów z 'is'T, ==F
print(0.0 is 0, 0.0 == 0) # False True

#ponadto, "is" nie jest tym samym co porównanie id() http://codepad.org/Xb0TaKl9

