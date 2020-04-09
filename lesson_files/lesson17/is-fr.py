L = ['texte', 11]
LEncore = L               # une autre référence à L
print(LEncore is L)       # même identité? oui
LCopiee = L[:]            # faire une copie
print(LCopiee == LEncore) # mêmes valeurs? oui: les deux sont ['texte', 11]
print(LCopiee is LEncore) # même identité? non: LEncore est L, LCopiee ne l'est
