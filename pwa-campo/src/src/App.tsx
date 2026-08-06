import React, { useEffect, useState } from 'react';
import { AppShell } from './components/AppShell';
import { BottomNav, TabType } from './components/BottomNav';
import { LoginScreen } from './screens/LoginScreen';
import { AgendaScreen } from './screens/AgendaScreen';
import { ChecklistScreen } from './screens/ChecklistScreen';
import { SummaryScreen } from './screens/SummaryScreen';
import { InspectionsScreen } from './screens/InspectionsScreen';
import { ReportsScreen } from './screens/ReportsScreen';
import { SettingsScreen } from './screens/SettingsScreen';
import { PdfViewerModal } from './components/PdfViewerModal';
import { InstallPwaModal } from './components/InstallPwaModal';
import { Session, InspectionPackage, ReportItem } from './types';
import { getSessionLocal, getPackageLocal } from './db';
import { checkSessionAPI, getPackageAPI } from './api';

export default function App() {
  const [session, setSession] = useState<Session | null>(null);
  const [isInitializing, setIsInitializing] = useState<boolean>(true);
  const [activeTab, setActiveTab] = useState<TabType>('agenda');

  // Inspection flow state
  const [activeChecklistPkg, setActiveChecklistPkg] = useState<InspectionPackage | null>(null);
  const [currentStep, setCurrentStep] = useState<'list' | 'checklist' | 'summary'>('list');

  // Modal overlays
  const [pdfModalReportId, setPdfModalReportId] = useState<string | null>(null);
  const [pdfModalPkg, setPdfModalPkg] = useState<InspectionPackage | null>(null);
  const [pdfModalReportItem, setPdfModalReportItem] = useState<ReportItem | null>(null);
  
  const [showInstallModal, setShowInstallModal] = useState<boolean>(false);
  const [deferredPrompt, setDeferredPrompt] = useState<any>(null);

  // Initialize session & PWA listener
  useEffect(() => {
    async function initApp() {
      try {
        const localSession = await getSessionLocal();
        if (localSession) {
          setSession(localSession);
        }

        // Try validating session
        const res = await checkSessionAPI();
        if (res.ok && res.dados) {
          setSession(res.dados);
        }
      } catch (err) {
        console.warn('Iniciando app em modo offline com dados armazenados.');
      } finally {
        setIsInitializing(false);
      }
    }

    initApp();

    // Listen for PWA installation prompt
    const handleBeforeInstallPrompt = (e: Event) => {
      e.preventDefault();
      setDeferredPrompt(e);
    };

    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

    return () => {
      window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    };
  }, []);

  const handleLoginSuccess = (newSession: Session) => {
    setSession(newSession);
  };

  const handleLogout = () => {
    setSession(null);
    setActiveChecklistPkg(null);
    setCurrentStep('list');
  };

  // Open checklist for a specific agendamento
  const handleOpenChecklist = async (agendamentoId: string) => {
    let pkg = await getPackageLocal(agendamentoId);
    if (!pkg) {
      const res = await getPackageAPI(agendamentoId);
      if (res.ok && res.package) {
        pkg = res.package;
      }
    }

    if (pkg) {
      setActiveChecklistPkg(pkg);
      setCurrentStep('checklist');
    }
  };

  // View PDF report modal
  const handleOpenPdfReport = (reportId: string, pkg?: InspectionPackage | null, reportItem?: ReportItem | null) => {
    setPdfModalReportId(reportId);
    setPdfModalPkg(pkg || null);
    setPdfModalReportItem(reportItem || null);
  };

  if (isInitializing) {
    return (
      <div className="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-4">
        <div className="text-center space-y-3 animate-pulse">
          <div className="w-12 h-12 bg-blue-600 rounded-2xl mx-auto flex items-center justify-center text-amber-400 font-bold text-xl">
            AN
          </div>
          <p className="text-xs text-slate-400 font-mono">Iniciando Módulo de Campo NORMAM...</p>
        </div>
      </div>
    );
  }

  // Render Login Screen if no active session
  if (!session) {
    return <LoginScreen onLoginSuccess={handleLoginSuccess} />;
  }

  // Calculate App Shell header title
  let headerTitle = 'Agenda de Vistorias';
  let headerSubtitle: string | undefined = 'Agendamentos e pacotes de campo';

  if (currentStep === 'checklist' && activeChecklistPkg) {
    headerTitle = activeChecklistPkg.agendamento.embarcacao_nome;
    headerSubtitle = `Checklist NORMAM • ${activeChecklistPkg.agendamento.embarcacao_registro}`;
  } else if (currentStep === 'summary' && activeChecklistPkg) {
    headerTitle = 'Resumo da Vistoria';
    headerSubtitle = `Finalização • ${activeChecklistPkg.agendamento.embarcacao_nome}`;
  } else {
    switch (activeTab) {
      case 'agenda':
        headerTitle = 'Agenda de Vistorias';
        headerSubtitle = 'Atribuições e retornos A/S';
        break;
      case 'vistorias':
        headerTitle = 'Vistorias Baixadas';
        headerSubtitle = 'Pacotes de campo locais';
        break;
      case 'relatorios':
        headerTitle = 'Relatórios Transmitidos';
        headerSubtitle = 'Documentos em PDF auditáveis';
        break;
      case 'configuracoes':
        headerTitle = 'Configurações e Ajustes';
        headerSubtitle = 'Perfil e aplicativo PWA';
        break;
    }
  }

  const isChecklistActive = currentStep !== 'list';

  return (
    <>
      <AppShell
        title={headerTitle}
        subtitle={headerSubtitle}
        showBack={isChecklistActive}
        onBack={() => {
          if (currentStep === 'summary') {
            setCurrentStep('checklist');
          } else {
            setCurrentStep('list');
            setActiveChecklistPkg(null);
          }
        }}
      >
        {/* Active View Router */}
        {currentStep === 'checklist' && activeChecklistPkg ? (
          <ChecklistScreen
            packageData={activeChecklistPkg}
            onGoToSummary={(updatedPkg) => {
              setActiveChecklistPkg(updatedPkg);
              setCurrentStep('summary');
            }}
          />
        ) : currentStep === 'summary' && activeChecklistPkg ? (
          <SummaryScreen
            packageData={activeChecklistPkg}
            onBackToChecklist={() => setCurrentStep('checklist')}
            onViewPdfReport={(reportId, pkg) => handleOpenPdfReport(reportId, pkg)}
          />
        ) : (
          /* Primary Tabs Router */
          <>
            {activeTab === 'agenda' && (
              <AgendaScreen
                onOpenChecklist={handleOpenChecklist}
                onViewPdfReport={(reportUrl) => handleOpenPdfReport('url_ref', null, null)}
              />
            )}

            {activeTab === 'vistorias' && (
              <InspectionsScreen
                onOpenChecklist={handleOpenChecklist}
                onViewPdfReport={(reportId, pkg) => handleOpenPdfReport(reportId, pkg)}
              />
            )}

            {activeTab === 'relatorios' && (
              <ReportsScreen
                onViewPdfReport={(reportId, reportItem) => handleOpenPdfReport(reportId, null, reportItem)}
              />
            )}

            {activeTab === 'configuracoes' && (
              <SettingsScreen
                session={session}
                onOpenInstallModal={() => setShowInstallModal(true)}
                onLogout={handleLogout}
              />
            )}
          </>
        )}
      </AppShell>

      {/* Persistent Bottom Navigation bar when not in checklist editing flow */}
      {!isChecklistActive && (
        <BottomNav
          activeTab={activeTab}
          onTabChange={(tab) => {
            setActiveTab(tab);
            setCurrentStep('list');
          }}
        />
      )}

      {/* PDF Report Viewer Modal */}
      {pdfModalReportId && (
        <PdfViewerModal
          reportId={pdfModalReportId}
          pkg={pdfModalPkg}
          reportItem={pdfModalReportItem}
          onClose={() => setPdfModalReportId(null)}
        />
      )}

      {/* PWA Install Modal */}
      {showInstallModal && (
        <InstallPwaModal
          deferredPrompt={deferredPrompt}
          onClose={() => setShowInstallModal(false)}
        />
      )}
    </>
  );
}
