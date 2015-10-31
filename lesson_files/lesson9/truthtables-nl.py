print("  A      B       not A  not B  A and B  A or B")
print("----------------------------------------------")
for A in [False, True]:  # een "for" loop met een lijst komt in les 13 aan bod
    for B in [False, True]:
        print(A, " ", B, "   ", not A, " ", not B, " ", A and B, " ", A or B)	
