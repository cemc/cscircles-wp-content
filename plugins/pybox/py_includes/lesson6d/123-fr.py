# étape 1: obtenir l'entrée
timbitsRestant = int(input()) 
print("l'entrée est", timbitsRestant)

# étape 2: initialiser le coût total
prixTotal = 0

# étape 3: acheter autant de grandes boîtes comme vous pouvez
grandeBoites = timbitsRestant / 40
# mettre à jour le prix total :
prixTotal = prixTotal + grandeBoites * 6.19
# calculer le nombre de timbits encore nécessaires :
timbitsRestant = timbitsRestant - 40 * grandeBoites

print('grandeBoites est égale à', grandeBoites)
print('prixTotale est égale à', prixTotal)
print('maintenant timbitsRestant est égale à', timbitsRestant)
