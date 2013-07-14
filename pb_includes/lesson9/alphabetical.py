# if first letter is different, string starting closer to A is smaller
print('apple' < 'banana') ## gives True
# but capital letters are smaller than non-capital ones. (because of ord())
print('Zebra' < 'abacus') ## gives True
# if first letters are identical, we compare the second letters, etc
print('apple' < 'aquarium') ## gives True
print('aquarium' < 'aquarius') ## gives True
# if all letters are the same but one string is shorter, shorter is smaller
print('aqua' < 'aquarium') ## gives True
