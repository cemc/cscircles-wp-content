# als de eerste letter verschilt wordt de eerste letter vergeleken
print('appel' < 'banaan')  # levert True op
# maar hoofdletters zijn altijd kleiner dan kleine letters (vanwege ord())
print('Zebra' < 'abacus')  # levert True op
# als de eerste letters gelijk zijn vergelijken we de tweede letters, etc.
print('appel' < 'aquarium')  # levert True op
print('aquarium' < 'aquarius')  # levert True op
# als alle letters gelijk zijn maar een van de twee strings korter is, is die kleiner
print('aqua' < 'aquarium')  # gives True
