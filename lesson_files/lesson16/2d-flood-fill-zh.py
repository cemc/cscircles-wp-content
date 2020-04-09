# 在Python中进行与二维网格相关的操作有多难? 
#   (比如，决定
#  一个城堡中房间数量，其中墙是'.')

# Python字符串不能更改，所以我们使用一序列的序列。

# 可读网格: 一序列字符串 
g = [ '..........',     
      '.oooo.ooo.',
      '......o.o.',
      '.oooo.ooo.',
      '..oo..ooo.',
      '..o...oo..',
      '..........'  ]

# 将一序列字符串转化为一序列的序列
g2 = []
for i in range(len(g)):
  g2.append(list(g[i]))

# 对网格进行操作。比如，label each connected region with a different colour

# 如何打印?
print(g2) #不对
for i in range(len(g2)): print(g2[i]) #还是不对
for row in range(len(g2)): print(''.join(g2[row]))
