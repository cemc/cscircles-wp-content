# étape 1: obtenir l'entrée
timbitsRestant = int(input()) 

# étape 2: initialiser le coût total
prixTotal = 0

# étape 3: acheter autant de grandes boîtes comme vous pouvez
grandeBoites = timbitsRestant // 40
# mettre à jour le prix total :
prixTotal = prixTotal + grandeBoites * 6.19
# calculer le nombre de timbits encore nécessaires :
timbitsRestant = timbitsRestant - 40 * grandeBoites

# étape 4, peut-on acheter une boîte moyenne?
if timbitsRestant >= 20:
    prixTotal = prixTotal + 3.39
    timbitsRestant = timbitsRestant - 20

# étape 5, peut-on acheter une petite boîte?
if timbitsRestant >= 10: 
    prixTotal = prixTotal + 1.99
    timbitsRestant = timbitsRestant - 20
                
prixTotal = prixTotal + timbitsRestant * 20 # étape 6
print(prixTotal)                            # étape 7
