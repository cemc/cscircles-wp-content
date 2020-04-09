timbitsLeft = int(input()) # 步骤1: 得到输入

print('the input is', timbitsLeft)

totalCost = 0              # 步骤2: 设定总计

# 步骤3: 尽可能多地购买大盒子
bigBoxes = timbitsLeft / 40
totalCost = totalCost + bigBoxes * 6.19    # 更新总计
timbitsLeft = timbitsLeft - 40 * bigBoxes  # 仍需计算timbits

print('bigBoxes equals', bigBoxes)
print('totalCost equals', totalCost)
print('now timbitsLeft equals', timbitsLeft)
