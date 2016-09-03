# 为什么 "is" 在字符串中十分“调皮”?
print("foo" is "foo", "foo" is "fo"+"o") # 都是对的, 由于字符串驻留
print("e"*32 is "ee"*16) # 错误, 长字符串不会被驻留
A = "foo"
B = "foofoo"
A *= 2
print(A == B, A is B) # 正确 错误: *= 不会重新驻留一个字符串

# 为什么 "is" 对于数字来说也十分“顽皮”?
print(1+1 is 2) # 正确, 但这种表现只针对小的整数
print(10**3 is 1000) # 错误
print(1.5 is 1.5, 1.5 is 0.5*3) # 正确, 错误
print(float('NaN')==float('NaN'), float('NaN') is float('NaN')) # 都是错误的
x = float('NaN')
print(x is x, x == x) # 正确 错误; 几个例子之一有关于 'is'T, ==F
print(0.0 is 0, 0.0 == 0) # 错误 正确

#同时, "is" 与比较 id() 并不相同。 http://codepad.org/Xb0TaKl9

