L = ['text', 11]
LAgain = L             # another reference to L
print(LAgain is L)     # Same Identity? Yes
LCopy = L[:]           # make a copy
print(LCopy == LAgain) # Same Values?   Yes, both are ['text', 11]
print(LCopy is LAgain) # Same Identity? No: LAgain is L, but LCopy is not L
