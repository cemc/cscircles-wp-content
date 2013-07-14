# pourquoi "is" est-il difficile pour chaines?
print("foo" is "foo", "foo" is "fo"+"o") # deux True, du a "interning"
print("e"*32 is "ee"*16) # False, chaines longues ne sont pas internes
A = "foo"
B = "foofoo"
A *= 2
print(A == B, A is B) # True False: *= ne re-interne pas un chaine

# pourquoi "is" est-il difficile pour nombres?
print(1+1 is 2) # True, mais seulment pour les nombres petits
print(10**3 is 1000) # False
print(1.5 is 1.5, 1.5 is 0.5*3) # True, False
print(float('NaN')==float('NaN'), float('NaN') is float('NaN')) # deux False
x = float('NaN')
print(x is x, x == x) # True False; peu de cas ont 'is'T, ==F
print(0.0 is 0, 0.0 == 0) # False True

# en outre, "is" fait plus que comparer id(): http://codepad.org/Xb0TaKl9

