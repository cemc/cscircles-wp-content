# Si les premières lettres sont différentes, l'une la plus première
# en l'alphabet determine la chaîne plus petite
print('ananas' < 'banane') ## True
# Mais les majuscules sont plus petites que celles miniscules (grâce à ord())
print('Zodiaque' < 'ananas') ## True
# Si les premières lettres sont identiques, on compare les deuxièmes, etc
print('chameau' < 'chenille') ## True
print('pomme' < 'pompe') ## True
# Si toutes les lettres sont les mêmes, mais une chaîne est plus courte,
# la plus courte est plus petit
print('bon' < 'bonnet') ## True
