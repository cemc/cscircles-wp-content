def nestedListSum(NL):
    if isinstance(NL, int):     # 例(a): NL是一个整数
        return NL               # 基本案例

    sum = 0                     # 例(b): NL是一序列的嵌套序列
    for i in range(0, len(NL)): # 主序列的每一部分都加到一起
        sum = sum + nestedListSum(NL[i])
    return sum                  # 结束
