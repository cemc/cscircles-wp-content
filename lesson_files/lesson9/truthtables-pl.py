print("  A      B       not A  not B  A and B  A or B")
print("----------------------------------------------")
for A in [False, True]:               # "for" petla z listą	
    for B in [False, True]:           # będzie wprowadzane w lekcji 13
        print(A, " ", B, "   ", not A, " ", not B, " ", A and B, " ", A or B)	
