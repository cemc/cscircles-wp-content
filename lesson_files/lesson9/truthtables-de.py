print("  A      B       not A  not B  A and B  A or B")
print("----------------------------------------------")
for A in [False, True]:               # "for"-Schleife mit einer Liste
    for B in [False, True]:           # werden wir in Lektion 13 kennenlernen
        print(A, " ", B, "   ", not A, " ", not B, " ", A and B, " ", A or B)	
