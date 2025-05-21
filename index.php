<?php
session_start();

// Duração máxima de inatividade (em segundos)
$tempoMaximoInatividade = 600;

// Verifica se o usuário está logado
if (!isset($_SESSION['nome'])) {
    // Usuário não está logado, redireciona para a página de login
    header("Location: login.html");
    exit;
}

// Verifica se a sessão expirou
if (isset($_SESSION['ultimo_acesso'])) {
    $tempoInativo = time() - $_SESSION['ultimo_acesso'];
    if ($tempoInativo > $tempoMaximoInatividade) {
        // Sessão expirou: limpa e destrói a sessão
        session_unset();
        session_destroy();
        header("Location: login.html?msg=expirada");
        exit;
    }
}

// Atualiza o tempo do último acesso para manter a sessão ativa
$_SESSION['ultimo_acesso'] = time();

// Define a variável para usar o nome do usuário no HTML
$usuario_nome = $_SESSION['nome'];

?>

<!DOCTYPE html>
<html lang="pt-BR"> 

<head>
  <meta charset="UTF-8">
  <title>CanedoBooks</title>
  <link rel="stylesheet" href="css/paginaInicial.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap">
</head>

<body>
  <header>
    <div class="logo">CanedoBooks</div>
    <nav>
  <ul>
    <?php if ($usuario_nome): ?>
      <li><strong>Bem-vindo, <?= htmlspecialchars($usuario_nome) ?></strong></li>
      <li><a href="logout.php">Sair</a></li>
    <?php else: ?>
      <li><a href="login.html">Login</a></li>
      <li><a href="cadastro.html">Cadastro</a></li>
    <?php endif; ?>
    <li><a href="contato.html">Contato</a></li>
  </ul>
</nav>

  </header>
  <div class="sidebar">
    <div class="search-container">
      <input type="text" id="search-title" placeholder="Buscar por título...">
      <input type="text" id="search-author" placeholder="Buscar por autor...">
      <button onclick="searchBooks()">Buscar</button>
    </div>
    <div class="categories">
      <h2>Categorias</h2>
      <ul>
        <li onclick="filterByCategory('Fantasia')">Fantasia</li>
        <li onclick="filterByCategory('Ficção Científica')">Ficção Científica</li>
        <li onclick="filterByCategory('Romance')">Romance</li>
        <li onclick="filterByCategory('Mistério')">Mistério</li>
        <li onclick="filterByCategory('Infantil')">Infantil</li>
      </ul>
    </div>
    <div id="cart">
      <h2>Carrinho</h2>
      <div id="cart-items"></div>
      <div id="cart-total">Total: R$ 0.00</div>
      <button id="checkout-btn" onclick="checkout()">Finalizar Compra</button>
    </div>
  </div>
  <div class="main-content">
    <div id="results"></div>
  </div>
  <div id="checkout-modal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Obrigado pela compra!</h2>
      <p>Valor total da compra: R$ <span id="modal-total"></span></p>
      <div style="display: flex; gap: 10px; margin-top: 20px; justify-content: center;">
        <button onclick="finalizarCompraDeVerdade()">Finalizar Compra</button>
        <button onclick="closeModal()">Continuar Comprando</button>
      </div>
    </div>
  </div>
  <script>
    const books = [
      { id: 1, title: "Harry Potter e a Pedra Filosofal", author: "J.K. Rowling", year: 1997, price: 39.90, category: "Fantasia", image: "https://m.media-amazon.com/images/I/51HSkTKlauL._SY344_BO1,204,203,200_QL70_ML2_.jpg" },
      { id: 2, title: "O Senhor dos Anéis: A Sociedade do Anel", author: "J.R.R. Tolkien", year: 1954, price: 49.90, category: "Fantasia", image: "https://img.elo7.com.br/product/zoom/2692717/poster-o-senhor-dos-aneis-a-sociedade-do-anel-lo02-90x60-cm-presente-geek.jpg" },
      { id: 3, title: "As Crônicas de Nárnia: O Leão, a Feiticeira e o Guarda-Roupa", author: "C.S. Lewis", year: 1950, price: 34.90, category: "Fantasia", image: "https://upload.wikimedia.org/wikipedia/pt/thumb/1/10/The_Chronicles_of_Narnia_-_The_Lion%2C_the_Witch_and_the_Wardrobe.jpg/250px-The_Chronicles_of_Narnia_-_The_Lion%2C_the_Witch_and_the_Wardrobe.jpg" },
      { id: 4, title: "O Nome do Vento", author: "Patrick Rothfuss", year: 2007, price: 44.90, category: "Fantasia", image: "https://m.media-amazon.com/images/I/81CGmkRG9GL._AC_UF1000,1000_QL80_.jpg" },
      { id: 5, title: "A Roda do Tempo: O Olho do Mundo", author: "Robert Jordan", year: 1990, price: 54.90, category: "Fantasia", image: "https://m.media-amazon.com/images/I/31fW-D5PGCL.jpg" },
      { id: 6, title: "A Cor da Magia", author: "Terry Pratchett", year: 1983, price: 29.90, category: "Fantasia", image: "https://m.media-amazon.com/images/I/611B4T3ApiL._AC_UF1000,1000_QL80_.jpg" },
      { id: 7, title: "O Último Desejo", author: "Andrzej Sapkowski", year: 1993, price: 36.90, category: "Fantasia", image: "https://m.media-amazon.com/images/I/81tPk-uLv7L._UF894,1000_QL80_.jpg" },
      { id: 8, title: "A Dança dos Dragões", author: "George R.R. Martin", year: 2011, price: 59.90, category: "Fantasia", image: "https://m.media-amazon.com/images/I/A1q+wZFZbGL._AC_UF1000,1000_QL80_.jpg" },
      { id: 9, title: "O Hobbit", author: "J.R.R. Tolkien", year: 1937, price: 32.90, category: "Fantasia", image: "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgO0rAL3_Pvfj-Sk5TvOZy6eu5PddNl7M0N0kE3ABluj8Vvh1CGGmkeohfGNfu5Ck3nDoZxibHh7yxLoy6mdiS5u3QxBcAZai0_x9FTgAGrNTk0Mfahr-xt0Lcp6R7weEWuf0p1RaUUH70/s1600/O_hobbit.jpg" },
      { id: 10, title: "A Bruxinha Atrapalhada", author: "Eva Furnari", year: 1982, price: 24.90, category: "Infantil", image: "https://m.media-amazon.com/images/I/71dkV+7m+5L._AC_UF1000,1000_QL80_.jpg" }
    ];

    let cart = [];

    function searchBooks() {
      const searchTitle = document.getElementById('search-title').value.toLowerCase();
      const searchAuthor = document.getElementById('search-author').value.toLowerCase();
      const results = books.filter(book =>
        book.title.toLowerCase().includes(searchTitle) &&
        book.author.toLowerCase().includes(searchAuthor)
      );
      displayResults(results);
    }

    function filterByCategory(category) {
      const results = books.filter(book => book.category === category);
      displayResults(results);
    }

    function displayResults(results) {
      const resultsDiv = document.getElementById('results');
      resultsDiv.innerHTML = '';
      if (results.length === 0) {
        resultsDiv.innerHTML = '<p>Nenhum livro encontrado.</p>';
        return;
      }
      results.forEach(book => {
        const bookDiv = document.createElement('div');
        bookDiv.classList.add('book');
        bookDiv.innerHTML = `
          <img src="${book.image}" alt="${book.title}">
          <h3>${book.title}</h3>
          <p>Autor: ${book.author}</p>
          <p>Ano: ${book.year}</p>
          <p>Categoria: ${book.category}</p>
          <p class="price">R$ ${book.price.toFixed(2)}</p>
          <button onclick="addToCart(${book.id})">Adicionar ao Carrinho</button>
        `;
        resultsDiv.appendChild(bookDiv);
      });
    }

    function addToCart(bookId) {
      const book = books.find(b => b.id === bookId);
      if (book) {
        cart.push(book);
        updateCart();
      }
    }

    function updateCart() {
      const cartItemsDiv = document.getElementById('cart-items');
      const cartTotalDiv = document.getElementById('cart-total');
      cartItemsDiv.innerHTML = '';
      let total = 0;
      cart.forEach((item, index) => {
        const itemDiv = document.createElement('div');
        itemDiv.classList.add('cart-item');
        itemDiv.innerHTML = `
          <span>${item.title}</span>
          <span>R$ ${item.price.toFixed(2)}</span>
          <span class="remove-item" onclick="removeFromCart(${index})">X</span>
        `;
        cartItemsDiv.appendChild(itemDiv);
        total += item.price;
      });
      cartTotalDiv.textContent = `Total: R$ ${total.toFixed(2)}`;
    }

    function removeFromCart(index) {
      cart.splice(index, 1);
      updateCart();
    }

    function checkout() {
      const modal = document.getElementById('checkout-modal');
      const modalTotal = document.getElementById('modal-total');
      const total = cart.reduce((sum, item) => sum + item.price, 0);
      modalTotal.textContent = total.toFixed(2);
      modal.style.display = 'block';
    }

    function finalizarCompraDeVerdade() {
      cart = [];
      updateCart();
      closeModal();
      window.location.href = 'Finalizarcompra.html';
    }

    function closeModal() {
      document.getElementById('checkout-modal').style.display = 'none';
    }

    const modal = document.getElementById('checkout-modal');
    const closeBtn = document.getElementsByClassName('close')[0];
    closeBtn.onclick = closeModal;
    window.onclick = function (event) {
      if (event.target == modal) closeModal();
    }

    displayResults(books);
  </script>
</body>

</html>