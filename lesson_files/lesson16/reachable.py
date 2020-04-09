S = [ [1], [2, 3], [4, 5], [6, 7], [], [], [100], [] ]

def pathto100(i):
  if i == 100:
    print(i)
    return True
  for j in S[i]:
    if pathto100(j) == True:
      print(i)
      return True
pathto100(0)
