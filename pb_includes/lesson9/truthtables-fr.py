print("  A      B       not A  not B  A and B  A or B")
print("----------------------------------------------")
for A in [False, True]:               # (boucle "for" avec liste
    for B in [False, True]:           #  sera expliquée plus tard)
        print(A, " ", B, "   ", not A, " ", not B, " ", A and B, " ", A or B)
