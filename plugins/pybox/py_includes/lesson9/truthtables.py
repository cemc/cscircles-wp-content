print("  A      B       not A  not B  A and B  A or B")
print("----------------------------------------------")
for A in [False, True]:               # "for" loop with list	
    for B in [False, True]:           # will be taught in lesson 13
        print(A, " ", B, "   ", not A, " ", not B, " ", A and B, " ", A or B)	
