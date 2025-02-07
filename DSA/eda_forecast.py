# %% Importar bibliotecas
import sys
import os
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns

# %% Criar conexão com o banco
sys.path.append(os.path.abspath("C:/xampp/htdocs/forecast/includes/"))
from db_connection import get_db_connection
engine = get_db_connection()

# %% Executando queries corretamente
query_faturamento = "SELECT * FROM V_FATURAMENTO"
query_forecast = "SELECT * FROM Forecast_pcp"
query_itens = "SELECT * FROM V_DEPARA_ITEM"
query_centro_distribuicao = "SELECT * FROM centros_distribuicao"

df_faturamento = pd.read_sql(query_faturamento, engine)
df_forecast = pd.read_sql(query_forecast, engine)
df_itens = pd.read_sql(query_itens, engine)
df_centro_distribuicao = pd.read_sql(query_centro_distribuicao, engine)


# %% Exibir primeiras linhas
print("Dados de v_faturamento:")
print(df_faturamento.head())

# %%
print("\nDados de forecast_pcp:")
print(df_forecast.head())

# %%
print("\nValores Nulos por Coluna:")
print(df_faturamento.isnull().sum())

# %%
print("\nEstatísticas Descritivas:")
print(df_faturamento.describe())

# %%
df_faturamento.hist(figsize=(12, 8), bins=30)
plt.suptitle("Distribuição das Variáveis Numéricas", fontsize=16)
plt.show()

# %%
df_faturamento['Data_Faturamento'] = pd.to_datetime(df_faturamento['Data_Faturamento'])

# %%
plt.figure(figsize=(12, 6))
sns.lineplot(x="Data_Faturamento", y="Quantidade", data=df_faturamento)
plt.title("Evolução do Faturamento ao Longo do Tempo")
plt.xticks(rotation=45)
plt.show()

# %%
plt.figure(figsize=(10, 6))
sns.heatmap(df_faturamento.corr(), annot=True, cmap="coolwarm", fmt=".2f")
plt.title("Mapa de Correlação entre as Variáveis")
plt.show()


# %%
# Filtrar apenas colunas numéricas para evitar erros
df_corr = df_faturamento.select_dtypes(include=["number"]).corr()

# Criar o mapa de calor apenas com os números
plt.figure(figsize=(10, 6))
sns.heatmap(df_corr, annot=True, cmap="coolwarm", fmt=".2f", linewidths=0.5)
plt.title("Mapa de Correlação entre as Variáveis Numéricas")
plt.show()

# %%
df_produtos = df_faturamento.groupby("Cod_produto")["Quantidade"].sum().reset_index()

plt.figure(figsize=(12, 6))
sns.barplot(x="Cod_produto", y="Quantidade", data=df_produtos.sort_values(by="Quantidade", ascending=False)[:10])
plt.xticks(rotation=90)
plt.title("Top 10 Produtos Mais Vendidos")
plt.show()



# %%
print("\n📌 Estrutura das Tabelas:")
print("🔹 Faturamento:")
print(df_faturamento.info())
# %%
print("\n🔹 Forecast:")
print(df_forecast.info())
# %%
print("\n🔹 Itens:")
print(df_itens.info())

# %%
print("\n🔹 Centro de Distribuição:")
print(df_centro_distribuicao.info())
# %% Estatísticas Descritivas
print("\n📊 Estatísticas Descritivas:")
print("🔹 Faturamento:")
print(df_faturamento.describe())

print("\n🔹 Forecast:")
print(df_forecast.describe())

print("\n🔹 Itens:")
print(df_itens.describe())

print("\n🔹 Centro de Distribuição:")
print(df_centro_distribuicao.describe())

# %%
print("\n🔍 Valores Nulos:")
print("🔹 Faturamento:")
print(df_faturamento.isnull().sum())

print("\n🔹 Forecast:")
print(df_forecast.isnull().sum())

print("\n🔹 Itens:")
print(df_itens.isnull().sum())

print("\n🔹 Centro de Distribuição:")
print(df_centro_distribuicao.isnull().sum())

# %%
# Selecionar apenas colunas numéricas
df_corr_faturamento = df_faturamento.select_dtypes(include=["number"]).corr()
df_corr_forecast = df_forecast.select_dtypes(include=["number"]).corr()

# Plotar a matriz de correlação do Faturamento
plt.figure(figsize=(10, 6))
sns.heatmap(df_corr_faturamento, annot=True, cmap="coolwarm", fmt=".2f")
plt.title("📌 Matriz de Correlação - Faturamento")
plt.show()

# Plotar a matriz de correlação do Forecast
plt.figure(figsize=(10, 6))
sns.heatmap(df_corr_forecast, annot=True, cmap="coolwarm", fmt=".2f")
plt.title("📌 Matriz de Correlação - Forecast")
plt.show()

# %%
df_faturamento["Data_Faturamento"] = pd.to_datetime(df_faturamento["Data_Faturamento"])

plt.figure(figsize=(12, 6))
sns.lineplot(x="Data_Faturamento", y="Quantidade", data=df_faturamento)
plt.title("📈 Evolução do Faturamento ao Longo do Tempo")
plt.xticks(rotation=45)
plt.show()

# %%
# Agrupar faturamento por produto
df_produtos = df_faturamento.groupby("Cod_produto")["Quantidade"].sum().reset_index()

plt.figure(figsize=(12, 6))
sns.barplot(x="Cod_produto", y="Quantidade", data=df_produtos.sort_values(by="Quantidade", ascending=False)[:10])
plt.xticks(rotation=90)
plt.title("📦 Top 10 Produtos Mais Vendidos")
plt.show()

# Agrupar por centro de distribuição
df_centro = df_faturamento.groupby("Cod_regional")["Quantidade"].sum().reset_index()

plt.figure(figsize=(12, 6))
sns.barplot(x="Cod_regional", y="Quantidade", data=df_centro.sort_values(by="Quantidade", ascending=False))
plt.xticks(rotation=90)
plt.title("🏭 Volume de Faturamento por Centro de Distribuição")
plt.show()


# %%
