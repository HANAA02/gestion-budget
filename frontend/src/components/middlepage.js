import React from 'react';

function MiddlePage() {
  return (
    <div style={styles.container}>
      <div style={styles.banner}>
        <div style={styles.bannerText}>
          Prenez le contrôle de vos finances dès aujourd’hui !
        </div>
        <img
          src=""
          alt="Illustration"
          style={styles.bannerImage}
        />
      </div>

      <div style={styles.infoBox}>
        Cette plateforme vous aide à suivre vos revenus et dépenses, définir vos objectifs financiers, recevoir des alertes, et analyser votre situation financière grâce à des graphiques clairs et des outils simples.
      </div>
    </div>
  );
}

const styles = {
  container: {
    width: '100%',
    fontFamily: 'Arial, sans-serif',
    paddingBottom: '40px', // un peu d'espace pour ne pas coller au footer
  },
  banner: {
    backgroundColor: '#800020',
    color: 'black',
    padding: '15px 30px',
    margin: '20px',
    fontSize: '20px',
    height: '200px',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderRadius: '8px 8px 0 0',
  },
  bannerText: {
    fontSize: '55px',
    fontWeight: '600',
  },
  bannerImage: {
    width: '40px',
    height: '40px',
  },
  infoBox: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'white',
    boxShadow: '0 4px 8px rgba(0, 0, 0, 0.1)',
    padding: '20px',
    marginTop: '30px',
    fontSize: '18px',
    borderRadius: '8px',
    textAlign: 'center',
    maxWidth: '800px',
    marginLeft: 'auto',
    marginRight: 'auto',
  },
};

export default MiddlePage;
