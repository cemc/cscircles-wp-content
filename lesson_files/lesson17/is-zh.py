L = ['text', 11]
LAgain = L             # L的另一个引用
print(LAgain is L)     # 同样的身份? 是的
LCopy = L[:]           # 做了一份拷贝
print(LCopy == LAgain) # 相同的值?   是的, 他们都是 ['text', 11]
print(LCopy is LAgain) # 同样的身份? 不: LAgain 是 L, 但 LCopy 不是 L
