T = (3, 4, 5)
print(T)
print(type(T))           # 元组
print(T[0])              # 元组中的第一项
print(list(T))           # 将一个元组转换为一个列表
print(tuple([1, 2, 3]))  # 将一个列表转换为一个元组
T[0] = "three"           # 错误! 你无法改变元组值
