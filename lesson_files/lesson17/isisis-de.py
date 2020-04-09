# Warum "is" bei Strings kompliziert ist:
print("foo" is "foo", "foo" is "fo"+"o") # in beiden Fällen True, wegen "string interning"
                                         # (gleiche Strings werden nur einmal gespeichert).
print("e"*32 is "ee"*16) # False, lange Strings werden nicht "interned".
A = "foo"
B = "foofoo"
A *= 2
print(A == B, A is B) # True False: *= bewirkt kein re-intern eines Strings

# Warum "is" bei Zahlen kompliziert ist:
print(1+1 is 2) # True, funktioniert so aber nur für kleine Integer
print(10**3 is 1000) # False
print(1.5 is 1.5, 1.5 is 0.5*3) # True, False
print(float('NaN')==float('NaN'), float('NaN') is float('NaN')) # beide False
x = float('NaN')
print(x is x, x == x) # True False; eines der ganz wenigen Beispiele mit 'is'T, ==F
print(0.0 is 0, 0.0 == 0) # False True

#übrigens: "is" entspricht nicht dem Vergleichen von id() Werten; siehe http://codepad.org/Xb0TaKl9

