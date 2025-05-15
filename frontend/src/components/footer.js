import React from 'react';

const Footer = () => {
  const styles = {
    footer: {
      backgroundColor: '#E5E5E5',
      color: 'black',
      padding: '40px 20px',
      display: 'flex',
      justifyContent: 'space-around',
      flexWrap: 'wrap',
      marginTop: '60px'
    },
    section: {
      flex: '1 1 250px',
      margin: '10px',
    },
    title: {
      fontWeight: 'bold',
      fontSize: '18px',
      marginBottom: '10px'
    },
    link: {
      display: 'block',
      color: 'white',
      textDecoration: 'none',
      marginBottom: '8px'
    },
    contactItem: {
      marginBottom: '8px'
    }
  };

  return (
    <footer style={styles.footer}>
      <div style={styles.section}>
        <div style={styles.title}>À propos de l'application</div>
        <p>
          <strong>BudgetZen</strong> est une plateforme intuitive qui vous aide à gérer vos revenus, 
          suivre vos dépenses et atteindre vos objectifs financiers avec sérénité.
        </p>
      </div>

      <div style={styles.section}>
        <div style={styles.title}>Liens rapides</div>
        <a href="#home" style={styles.link}>🏠 Accueil</a>
        <a href="#about" style={styles.link}>ℹ️ À propos</a>
        <a href="#login" style={styles.link}>🔐 Connexion</a>
        <a href="#signup" style={styles.link}>📝 Inscription</a>
      </div>

      <div style={styles.section}>
        <div style={styles.title}>Informations de contact</div>
        <p style={styles.contactItem}>📧 opibudget@gmail.com</p>
        <p style={styles.contactItem}>📞 +212-7724569</p>
        <p style={styles.contactItem}>📘 Facebook</p>
        <p style={styles.contactItem}>🐦 Twitter</p>
        <p style={styles.contactItem}>🔗 LinkedIn</p>
      </div>
    </footer>
  );
};

export default Footer;
