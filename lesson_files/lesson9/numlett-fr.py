x = int(input())
if x>=1 and x<=26:
    print('lettre', x, "dans l'alphabet:", chr(ord('A')+(x-1)))
else:
    print('entrée non valide:', x)

