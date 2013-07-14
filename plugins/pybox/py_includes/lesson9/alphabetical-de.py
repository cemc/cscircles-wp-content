# wenn der erste Buchstabe unterschiedlich ist, ist der, der näher an A ist kleiner
print('apple' < 'banana') ## True
# Großgeschriebene Buchstaben sind immer kleiner als kleingeschriebene. (wegen ord())
print('Zebra' < 'abacus') ## True
# Ist der erste Buchstabe gleich wird der zweite verglichen, usw.
print('apple' < 'aquarium') ## True
print('aquarium' < 'aquarius') ## True
# sind die Buchstaben gleich, eine Zeichenkette jedoch kürzer ist diese auch kleiner
print('aqua' < 'aquarium') ## True
