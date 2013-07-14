# why is "is" tricksy for strings?
print("foo" is "foo", "foo" is "fo"+"o") # both True, due to string interning
print("e"*32 is "ee"*16) # False, long strings are not interned
A = "foo"
B = "foofoo"
A *= 2
print(A == B, A is B) # True False: *= does not re-intern a string

# why is "is" tricksy for numbers?
print(1+1 is 2) # True, but this behaviour is only for small ints
print(10**3 is 1000) # False
print(1.5 is 1.5, 1.5 is 0.5*3) # True, False
print(float('NaN')==float('NaN'), float('NaN') is float('NaN')) # both False
x = float('NaN')
print(x is x, x == x) # True False; one of few examples with 'is'T, ==F
print(0.0 is 0, 0.0 == 0) # False True

#also, "is" not same as compare id() http://codepad.org/Xb0TaKl9

