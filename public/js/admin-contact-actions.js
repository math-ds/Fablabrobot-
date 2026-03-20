(() => {
  function filtreContactActif() {
    const boutonActif = document.querySelector(".filters .filter-btn[data-contact-filter].active");
    if (!(boutonActif instanceof HTMLElement)) {
      return "all";
    }

    const valeur = String(boutonActif.getAttribute("data-contact-filter") || "all")
      .trim()
      .toLowerCase();
    return valeur === "" ? "all" : valeur;
  }

  window.supprimerMessage = async function supprimerMessage(id, nom) {
    if (!window.confirm(`Êtes-vous sûr de vouloir supprimer le message de "${nom}" ?`)) {
      return;
    }

    try {
      const data = await AjaxHelper.post("?page=admin-contact", {
        action: "delete",
        contact_id: id,
      });

      if (data.success) {
        ToastNotification.succes(data.message || "Message supprimé avec succès");

        if (
          window.AdminDashboardAjax &&
          typeof window.AdminDashboardAjax.refreshAfterDelete === "function"
        ) {
          await window.AdminDashboardAjax.refreshAfterDelete({
            deletedRows: 1,
            preserveLocalState: false,
            scrollToTop: false,
          });
        } else {
          window.location.reload();
        }
      }
    } catch (error) {
      ToastNotification.erreur(error.data?.message || "Erreur lors de la suppression");
    }
  };

  window.changerStatut = async function changerStatut(id, statut, nom) {
    try {
      const filtreActifAvantAction = filtreContactActif();
      const nouveauStatut = String(statut || "")
        .trim()
        .toLowerCase();
      const ligneSortDuFiltre =
        filtreActifAvantAction !== "all" &&
        nouveauStatut !== "" &&
        filtreActifAvantAction !== nouveauStatut;

      const data = await AjaxHelper.post("?page=admin-contact", {
        action: statut,
        contact_id: id,
        nom,
      });

      if (data.success) {
        ToastNotification.succes(data.message);

        if (
          window.AdminDashboardAjax &&
          typeof window.AdminDashboardAjax.refreshAfterDelete === "function" &&
          ligneSortDuFiltre
        ) {
          await window.AdminDashboardAjax.refreshAfterDelete({
            deletedRows: 1,
            preserveLocalState: false,
            scrollToTop: false,
          });
        } else if (
          window.AdminDashboardAjax &&
          typeof window.AdminDashboardAjax.refreshCurrent === "function"
        ) {
          await window.AdminDashboardAjax.refreshCurrent({
            preserveLocalState: false,
            scrollToTop: false,
          });
        } else {
          window.location.reload();
        }
      }
    } catch (error) {
      ToastNotification.erreur(error.data?.message || "Erreur lors du changement de statut");
    }
  };
})();
