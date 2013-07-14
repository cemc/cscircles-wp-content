def listeImbriqueeSomme(LI):
    if isinstance(LI, int):     # (a): LI est un nombre entier
        return LI               # cas de base

    somme = 0                   # (b): LI est une liste des listes imbriquees
    for i in range(0, len(LI)): # ajouter chaque partie de la liste principale
        somme = somme + listeImbriqueeSomme(LI[i])
    return somme                # tout est fait
