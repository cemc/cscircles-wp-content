# jeśli pierwsze litery są rózne, to łańcuch z literą bliżej A jest mniejszy
print('apple' < 'banana') ## zwraca True
# ale duże litery są mniejsze od małych (ponieważ ord())
print('Zebra' < 'abacus') ## zwraca True
# jeżeli pierwszelitery są identyczne, to porównujemy drugie, itp.
print('apple' < 'aquarium') ## zwraca True
print('aquarium' < 'aquarius') ## zwraca True
# jeśli wszystkie litery są takie same, ale jeden łańcuch jest krutszy, to krótszy jest mniejszy
print('aqua' < 'aquarium') ## zwraca True
