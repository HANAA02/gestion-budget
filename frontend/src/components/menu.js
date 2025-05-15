import React from 'react';

function Menu() {
  return (
    <header style={styles.header}>
      {/* Logo à gauche */}
      <div style={styles.logo}>
        <span style={styles.logoO}>O</span>
        <span style={styles.logoText}>ptibudget</span>
      </div>

      {/* Menu à droite */}
      <nav>
        <ul style={styles.menu}>
          <li><a href="#accueil" style={styles.link}>Accueil</a></li>
          <li><a href="#apropos" style={styles.link}>À propos</a></li>
          <li><a href="#connexion" style={styles.link}>Connexion</a></li>
          <li><a href="#inscription" style={styles.link}>Inscription</a></li>
        </ul>
      </nav>
    </header>
  );
}

const styles = {
  header: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: '10px 30px',
    borderBottom: '1px solid #ddd',
    fontFamily: "'Arial Italic', Arial, sans-serif",
  },
  logo: {
    fontStyle: 'italic',
    fontSize: '28px',
    fontWeight: 'bold',
    cursor: 'default',
  },
  logoO: {
    color: 'bordeaux',  // Bordeaux n'est pas une couleur CSS standard, on met un code hex à la place
    color: '#800020',    // Couleur bordeaux foncé
    fontStyle: 'italic',
  },
  logoText: {
    color: 'black',
    fontStyle: 'italic',
    marginLeft: '5px',
  },
  menu: {
    listStyle: 'none',
    display: 'flex',
    gap: '25px',
    margin: 0,
    padding: 0,
  },
  link: {
    textDecoration: 'none',
    color: 'black',
    fontWeight: '600',
    fontSize: '16px',
    cursor: 'pointer',
  }
};

export default Menu;
