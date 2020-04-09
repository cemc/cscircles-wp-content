tailleOriginale = ["lettre", 8.5, 11]          # format de papier en pouces
tailleConvertie = tailleOriginale              # faire une copie
tailleConvertie[1] = tailleConvertie[1] * 2.54 # convertir en cm
tailleConvertie[2] = tailleConvertie[2] * 2.54 # convertir en cm
print(tailleConvertie)            # comme prevu ["lettre", 21.59, 27.94]
print(tailleOriginale)            # pas comme prevu! les nombres ont change
