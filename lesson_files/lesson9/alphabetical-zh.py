# 如果第一个字母不一样，靠近A的更小
print('apple' < 'banana') ## 返还True
# 但是大写字母小于非大写字母。 (因为ord())
print('Zebra' < 'abacus') ## 返还True
# 如果第一个字母一样, 我们再比较第二个字母等
print('apple' < 'aquarium') ## 返还True
print('aquarium' < 'aquarius') ## 返还True
# 如果所有的字母都一样，但是一个字符串更短, 更短的更小
print('aqua' < 'aquarium') ## 返还True
