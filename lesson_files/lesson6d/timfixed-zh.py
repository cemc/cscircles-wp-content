timbitsLeft = int(input()) # 步骤1: 得到输入
totalCost = 0              # 步骤 2: 设定总计

# 步骤3: 尽可能多地购买大盒子
bigBoxes = int(timbitsLeft / 40)
totalCost = totalCost + bigBoxes * 6.19    # 更新总计
timbitsLeft = timbitsLeft - 40 * bigBoxes  # 仍需计算timbits

if timbitsLeft >= 20:                # 步骤4, 我们能买一个中盒子么?
    totalCost = totalCost + 3.39
    timbitsLeft = timbitsLeft - 20
if timbitsLeft >= 10:                # 步骤5, 我们能买一个小盒子么?
    totalCost = totalCost + 1.99
    timbitsLeft = timbitsLeft - 10

totalCost = totalCost + timbitsLeft * 0.20 # 步骤6
print(totalCost)                         # 步骤7
